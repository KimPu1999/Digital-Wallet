<?php
namespace App\Domain\Transfers;
use Illuminate\Validation\ValidationException;
final class TransferStateMachine
{
    private const MAP = [
        'draft'=>['quoted','cancelled','expired'],
        'quoted'=>['awaiting_confirmation','expired','cancelled'],
        'awaiting_confirmation'=>['pending_compliance','cancelled','expired'],
        'pending_compliance'=>['processing','rejected','failed','cancelled'],
        'processing'=>['completed','failed','cancelled','returned'],
        'completed'=>['returned'],
        'rejected'=>[], 'failed'=>[], 'cancelled'=>[], 'expired'=>[], 'returned'=>[],
    ];
    public static function assert(string $from, string $to): void { if (!in_array($to, self::MAP[$from] ?? [], true)) throw ValidationException::withMessages(['status'=>"Transfer cannot move from {$from} to {$to}."]); }
}
