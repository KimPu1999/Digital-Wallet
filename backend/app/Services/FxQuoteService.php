<?php
namespace App\Services;

use App\Domain\Fx\FxMath;
use App\Models\FxFeeRule;
use App\Models\FxAuditEvent;
use App\Models\FxPair;
use App\Models\FxQuote;
use App\Models\FxRateProvider;
use App\Models\FxRateSnapshot;
use App\Models\User;
use App\Models\WalletCurrencyAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FxQuoteService
{
    public function __construct(private FxRateProviderRegistry $providers, private CountryEligibilityService $eligibility) {}

    public function create(User $user, WalletCurrencyAccount $source, WalletCurrencyAccount $target, int $amountMinor): FxQuote
    {
        $source->loadMissing(['wallet.owner','currency']); $target->loadMissing(['wallet.owner','currency']);
        if (!app(\App\Policies\WalletPolicy::class)->view($user, $source->wallet) || !app(\App\Policies\WalletPolicy::class)->view($user, $target->wallet)) throw ValidationException::withMessages(['wallet'=>'Wallet access denied.']);
        if ($source->id === $target->id || $source->currency->code === $target->currency->code) throw ValidationException::withMessages(['pair'=>'Source and target currencies must be different.']);
        if (!$this->eligibility->capability((string)$user->country_code, 'fx_enabled')) throw ValidationException::withMessages(['country_code'=>'FX is not available for this country in the current sandbox.']);
        $pair=FxPair::where('base_currency_code',$source->currency->code)->where('quote_currency_code',$target->currency->code)->where('is_enabled',true)->where('provider_available',true)->first();
        if(!$pair) throw ValidationException::withMessages(['pair'=>'This currency pair is not available.']);
        if($amountMinor < (int)$pair->min_amount_minor || ($pair->max_amount_minor !== null && $amountMinor > (int)$pair->max_amount_minor)) throw ValidationException::withMessages(['source_amount_minor'=>'Amount is outside the configured sandbox limits.']);
        return DB::transaction(function() use($user,$source,$target,$amountMinor,$pair): FxQuote {
            $provider=FxRateProvider::where('code','sandbox-mock')->where('status','active')->firstOrFail();
            $rate=$this->providers->get($provider->code)->rate($pair);
            $snapshot=FxRateSnapshot::create(['provider_id'=>$provider->id,'pair_id'=>$pair->id,'rate'=>$rate->rate,'inverse_rate'=>function_exists('bcdiv')?bcdiv('1',$rate->rate,18):null,'observed_at'=>now(),'expires_at'=>now()->addSeconds($rate->ttlSeconds),'provider_reference'=>$rate->providerReference,'status'=>'fresh','metadata'=>$rate->metadata]);
            $rule=FxFeeRule::where('pair_id',$pair->id)->where('is_enabled',true)->first() ?? new FxFeeRule(['fixed_fee_minor'=>0,'percentage_bps'=>0,'provider_spread_bps'=>0,'platform_markup_bps'=>0,'minimum_fee_minor'=>0]);
            $fee=FxMath::fee($amountMinor,(int)$rule->fixed_fee_minor,(int)$rule->percentage_bps,(int)$rule->minimum_fee_minor,$rule->maximum_fee_minor === null ? null : (int)$rule->maximum_fee_minor);
            $spread=(int)$rule->provider_spread_bps+(int)$rule->platform_markup_bps;
            $effective=FxMath::rateAfterBps($rate->rate,$spread);
            $targetAmount=FxMath::target($amountMinor,$effective);
            $quote=FxQuote::create(['quote_reference'=>'FXQ-'.strtoupper(Str::random(20)),'user_id'=>$user->id,'source_wallet_currency_account_id'=>$source->id,'target_wallet_currency_account_id'=>$target->id,'pair_id'=>$pair->id,'rate_snapshot_id'=>$snapshot->id,'source_currency_code'=>$source->currency->code,'target_currency_code'=>$target->currency->code,'source_amount_minor'=>$amountMinor,'mid_market_rate'=>$rate->rate,'exchange_rate'=>$effective,'rate_direction'=>'base_to_quote','provider_spread_bps'=>(int)$rule->provider_spread_bps,'platform_markup_bps'=>(int)$rule->platform_markup_bps,'platform_fee_minor'=>$fee,'provider_reference'=>$rate->providerReference,'estimated_target_amount_minor'=>$targetAmount,'rounding_policy'=>'floor_target_minor','status'=>'quoted','sandbox_only'=>true,'expires_at'=>$snapshot->expires_at,'metadata'=>['provider'=>$provider->code,'sandbox'=>true]]);
            FxAuditEvent::create(['actor_id'=>$user->id,'quote_id'=>$quote->id,'action'=>'fx.quote.created','metadata'=>['source_currency'=>$source->currency->code,'target_currency'=>$target->currency->code,'sandbox_only'=>true]]);
            return $quote->load(['pair','sourceAccount.currency','targetAccount.currency']);
        });
    }
}
