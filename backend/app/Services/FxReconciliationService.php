<?php
namespace App\Services;
use App\Models\FxConversion;
use App\Models\FxReconciliationItem;

final class FxReconciliationService
{
    public function run(?string $actorId = null): array
    {
        $checked=0; $exceptions=0;
        FxConversion::with(['legs','ledgerTransaction'])->where('status','completed')->chunkById(100,function($conversions) use (&$checked,&$exceptions,$actorId): void {
            foreach($conversions as $conversion) {
                $checked++;
                $expectedSource=(int)$conversion->source_amount_minor+(int)$conversion->fee_minor;
                $sourceDebit=$conversion->legs->where('currency_code',$conversion->source_currency_code)->where('leg_type','source_wallet')->where('side','debit')->sum('amount_minor');
                $targetCredit=$conversion->legs->where('currency_code',$conversion->target_currency_code)->where('leg_type','target_wallet')->where('side','credit')->sum('amount_minor');
                $balanced=$conversion->ledgerTransaction?->status==='posted' && $expectedSource===(int)$sourceDebit && (int)$conversion->target_amount_minor===(int)$targetCredit;
                $status=$balanced?'matched':'exception'; if(!$balanced) $exceptions++;
                FxReconciliationItem::create(['conversion_id'=>$conversion->id,'item_type'=>'fx_conversion','status'=>$status,'expected_minor'=>$conversion->target_amount_minor,'actual_minor'=>$targetCredit,'difference_minor'=>(int)$targetCredit-(int)$conversion->target_amount_minor,'metadata'=>['actor_id'=>$actorId,'source_expected'=>$expectedSource,'source_actual'=>$sourceDebit,'ledger_transaction_id'=>$conversion->ledger_transaction_id]]);
            }
        },'id');
        return ['checked'=>$checked,'exceptions'=>$exceptions,'status'=>$exceptions===0?'completed':'completed_with_exceptions'];
    }
}
