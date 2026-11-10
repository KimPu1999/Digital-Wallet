<?php
namespace App\Services;
use App\Models\Transfer; use App\Models\TransferScreening; use Illuminate\Support\Str;
final class MockTransferScreeningService
{
    public function screen(Transfer $transfer): array { $types=['sanctions','pep','beneficiary_risk','country_risk','velocity']; $results=[]; foreach($types as $type) { $screen=TransferScreening::updateOrCreate(['transfer_id'=>$transfer->id,'screening_type'=>$type],['provider'=>'sandbox-mock','status'=>'approved','decision'=>'clear','reference'=>'sandbox-screen-'.Str::lower(Str::random(12)),'reason'=>'Development mock result; not a compliance clearance.','metadata'=>['sandbox_only'=>true]]); $results[]=$screen; } return $results; }
}
