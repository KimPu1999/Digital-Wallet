<?php
namespace App\Services;
use App\Models\Transfer; use App\Models\TransferStepUpChallenge; use App\Models\User; use Illuminate\Support\Str; use Illuminate\Validation\ValidationException;
final class TransferStepUpService
{
    public function issue(User $user, ?Transfer $transfer=null): array { $code=(string)random_int(100000,999999); $sessionId=request()->hasSession()?request()->session()->getId():''; $challenge=TransferStepUpChallenge::create(['user_id'=>$user->id,'transfer_id'=>$transfer?->id,'challenge_hash'=>hash('sha256',$code),'method'=>'mfa','status'=>'pending','expires_at'=>now()->addMinutes(5),'session_fingerprint'=>hash('sha256',(string)$sessionId)]); return ['challenge_id'=>$challenge->id,'expires_at'=>$challenge->expires_at,'sandbox_code'=>$code,'sandbox_only'=>true]; }
    public function consume(User $user, string $challengeId, string $code, ?Transfer $transfer=null): TransferStepUpChallenge { $challenge=TransferStepUpChallenge::whereKey($challengeId)->where('user_id',$user->id)->lockForUpdate()->firstOrFail(); if($challenge->status!=='pending'||$challenge->expires_at->isPast()||!hash_equals($challenge->challenge_hash,hash('sha256',$code))||($transfer&&$challenge->transfer_id!==$transfer->id)) throw ValidationException::withMessages(['step_up'=>'The step-up challenge is invalid, expired, or already consumed.']); $challenge->update(['status'=>'consumed','consumed_at'=>now()]); return $challenge; }
}
