<?php
namespace App\Services;
use App\Domain\Transfers\TransferStateMachine; use App\Models\Transfer; use App\Models\TransferEvent;
final class TransferStateService
{
    public function transition(Transfer $transfer, string $to, ?string $actorId, string $eventType, ?string $reason=null): Transfer { $from=$transfer->status; if($from!==$to) TransferStateMachine::assert($from,$to); $transfer->update(['status'=>$to,'completed_at'=>$to==='completed'?now():$transfer->completed_at,'cancelled_at'=>$to==='cancelled'?now():$transfer->cancelled_at,'failure_reason'=>in_array($to,['failed','rejected','returned'],true)?$reason:$transfer->failure_reason]); TransferEvent::create(['transfer_id'=>$transfer->id,'from_status'=>$from,'to_status'=>$to,'event_type'=>$eventType,'actor_id'=>$actorId,'reason'=>$reason,'metadata'=>['sandbox_only'=>true]]); return $transfer->refresh(); }
}
