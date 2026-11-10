<?php
namespace App\Services;

use App\Domain\Ledger\FinancialCommand;
use App\Domain\Ledger\LedgerLine;
use App\Domain\Fx\FxMath;
use App\Models\FxConversion;
use App\Models\FxConversionLeg;
use App\Models\FxQuote;
use App\Models\FxAuditEvent;
use App\Models\IdempotencyKey;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FxConversionService
{
    public function __construct(private LedgerPostingService $ledger, private ChartOfAccountsService $accounts, private IdempotencyService $idempotency) {}

    public function execute(User $user, FxQuote $quote, string $idempotencyKey): FxConversion
    {
        if ((string)$quote->user_id !== (string)$user->id) throw ValidationException::withMessages(['quote'=>'Quote access denied.']);
        if (!$quote->sandbox_only || $quote->isExpired()) throw ValidationException::withMessages(['quote'=>'This FX quote is expired or not eligible for sandbox execution.']);
        $payload=['quote_id'=>$quote->id,'source_amount_minor'=>$quote->source_amount_minor,'exchange_rate'=>(string)$quote->exchange_rate];
        $result=$this->idempotency->execute('fx.conversion.'.$user->id,$idempotencyKey,$payload,function() use($user,$quote,$idempotencyKey): array {
            return DB::transaction(function() use($user,$quote,$idempotencyKey): array {
                $quote=FxQuote::with(['sourceAccount.currency','targetAccount.currency'])->whereKey($quote->id)->lockForUpdate()->firstOrFail();
                if($quote->isExpired()) throw ValidationException::withMessages(['quote'=>'This FX quote has expired.']);
                if(FxConversion::where('quote_id',$quote->id)->exists()) throw ValidationException::withMessages(['quote'=>'This quote has already been consumed.']);
                $source=$quote->sourceAccount; $target=$quote->targetAccount;
                if(!$source || !$target || $source->currency->code !== $quote->source_currency_code || $target->currency->code !== $quote->target_currency_code) throw ValidationException::withMessages(['quote'=>'The quote accounts no longer match.']);
                $sourceLedger=$source->ledgerAccount()->firstOrFail(); $targetLedger=$target->ledgerAccount()->firstOrFail();
                $sourceClearing=$this->accounts->provisionSystemAccount('system.fx.clearing.'.$source->currency->code,'FX clearing '.$source->currency->code,'asset','debit',$source->currency->code,true);
                $targetClearing=$this->accounts->provisionSystemAccount('system.fx.clearing.'.$target->currency->code,'FX clearing '.$target->currency->code,'asset','debit',$target->currency->code,true);
                $feeAccount=$this->accounts->provisionSystemAccount('system.fx.fees.'.$source->currency->code,'FX fee revenue '.$source->currency->code,'revenue','credit',$source->currency->code,true);
                $sourceTotal=FxMath::add((int)$quote->source_amount_minor,(int)$quote->platform_fee_minor);
                $lines=[new LedgerLine($sourceLedger->id,$source->currency->code,'debit',$sourceTotal,['fx_leg'=>'source_wallet']),new LedgerLine($sourceClearing->id,$source->currency->code,'credit',(int)$quote->source_amount_minor,['fx_leg'=>'source_clearing'])];
                if((int)$quote->platform_fee_minor>0) $lines[]=new LedgerLine($feeAccount->id,$source->currency->code,'credit',(int)$quote->platform_fee_minor,['fx_leg'=>'platform_fee']);
                $lines[]=new LedgerLine($targetClearing->id,$target->currency->code,'debit',(int)$quote->estimated_target_amount_minor,['fx_leg'=>'target_clearing']);
                $lines[]=new LedgerLine($targetLedger->id,$target->currency->code,'credit',(int)$quote->estimated_target_amount_minor,['fx_leg'=>'target_wallet']);
                $transaction=$this->ledger->post(new FinancialCommand('fx_conversion','FX-'.$quote->quote_reference,$lines,'Sandbox FX conversion',['quote_id'=>$quote->id,'sandbox_only'=>true,'source_currency'=>$source->currency->code,'target_currency'=>$target->currency->code],true),$user->id);
                $idempotency=IdempotencyKey::where('scope','fx.conversion.'.$user->id)->where('key',$idempotencyKey)->first();
                $conversion=FxConversion::create(['quote_id'=>$quote->id,'user_id'=>$user->id,'source_wallet_currency_account_id'=>$source->id,'target_wallet_currency_account_id'=>$target->id,'source_currency_code'=>$source->currency->code,'target_currency_code'=>$target->currency->code,'source_amount_minor'=>$quote->source_amount_minor,'target_amount_minor'=>$quote->estimated_target_amount_minor,'fee_minor'=>$quote->platform_fee_minor,'exchange_rate'=>$quote->exchange_rate,'status'=>'completed','ledger_transaction_id'=>$transaction->id,'idempotency_key_id'=>$idempotency?->id,'sandbox_only'=>true,'completed_at'=>now(),'metadata'=>['sandbox_only'=>true,'provider_reference'=>$quote->provider_reference]]);
                foreach($transaction->entries as $entry) FxConversionLeg::create(['conversion_id'=>$conversion->id,'ledger_account_id'=>$entry->ledger_account_id,'currency_code'=>$entry->currency_code,'leg_type'=>$entry->metadata['fx_leg'] ?? 'ledger','side'=>$entry->side,'amount_minor'=>$entry->amount_minor,'sequence'=>$entry->sequence]);
                FxAuditEvent::create(['actor_id'=>$user->id,'quote_id'=>$quote->id,'conversion_id'=>$conversion->id,'action'=>'fx.conversion.completed','metadata'=>['ledger_transaction_id'=>$transaction->id,'sandbox_only'=>true]]);
                return ['conversion_id'=>$conversion->id];
            });
        });
        return FxConversion::with(['quote','legs.ledgerAccount','ledgerTransaction'])->findOrFail($result->value['conversion_id']);
    }

}
