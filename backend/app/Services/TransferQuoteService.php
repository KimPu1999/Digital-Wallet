<?php
namespace App\Services;
use App\Domain\Fx\FxMath; use App\Models\Beneficiary; use App\Models\BeneficiaryAccount; use App\Models\FxPair; use App\Models\KycCase; use App\Models\TransferQuote; use App\Models\User; use App\Models\WalletCurrencyAccount; use Illuminate\Support\Str; use Illuminate\Validation\ValidationException;
final class TransferQuoteService
{
    public function __construct(private CountryEligibilityService $eligibility) {}
    public function create(User $user, WalletCurrencyAccount $source, Beneficiary $beneficiary, BeneficiaryAccount $beneficiaryAccount, int $amount): TransferQuote
    {
        if(!app(\App\Policies\WalletPolicy::class)->view($user,$source->wallet()->with('owner')->firstOrFail())) throw ValidationException::withMessages(['wallet'=>'Source wallet access denied.']);
        if(!$beneficiary->isOwnedBy($user)||$beneficiary->status!=='active') throw ValidationException::withMessages(['beneficiary'=>'Beneficiary access denied or inactive.']);
        if(!$this->eligibility->capability((string)$user->country_code,'transfers_enabled')) throw ValidationException::withMessages(['country_code'=>'Transfers are not available for this country in the sandbox.']);
        if($this->eligibility->capability((string)$user->country_code,'kyc_enabled')&&!KycCase::where('user_id',$user->id)->where('status','approved')->exists()) throw ValidationException::withMessages(['kyc'=>'Approved KYC is required before transfers.']);
        $source->loadMissing('currency'); $destination=strtoupper((string)($beneficiaryAccount->currency_code ?: $source->currency->code)); $pair=null; $rate='1.000000000000000000'; $fxReference=null;
        if($destination!==$source->currency->code){ if(!$this->eligibility->capability((string)$user->country_code,'fx_enabled')) throw ValidationException::withMessages(['corridor'=>'This international corridor is not enabled in the sandbox.']); $pair=FxPair::where(['base_currency_code'=>$source->currency->code,'quote_currency_code'=>$destination,'is_enabled'=>true,'provider_available'=>true])->first(); if(!$pair) throw ValidationException::withMessages(['corridor'=>'This currency corridor is not supported.']); $provider=app(MockFxRateProvider::class)->rate($pair); $rate=$provider->rate; $fxReference=$provider->providerReference; }
        $platformFee=$destination===$source->currency->code?0:FxMath::fee($amount,0,50,0,null); $recipient=FxMath::target($amount,$rate);
        return TransferQuote::create(['quote_reference'=>'TQ-'.strtoupper(Str::random(20)),'user_id'=>$user->id,'source_wallet_currency_account_id'=>$source->id,'beneficiary_id'=>$beneficiary->id,'beneficiary_account_id'=>$beneficiaryAccount->id,'source_currency_code'=>$source->currency->code,'destination_currency_code'=>$destination,'source_amount_minor'=>$amount,'recipient_amount_minor'=>$recipient,'provider_fee_minor'=>0,'platform_fee_minor'=>$platformFee,'exchange_rate'=>$rate,'fx_quote_reference'=>$fxReference,'corridor_status'=>'sandbox_allowed','status'=>'quoted','sandbox_only'=>true,'expires_at'=>now()->addSeconds($pair?->quote_expiry_seconds ?? 60),'metadata'=>['sandbox_only'=>true]]);
    }
}
