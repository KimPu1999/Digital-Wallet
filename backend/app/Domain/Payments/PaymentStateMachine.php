<?php

namespace App\Domain\Payments;

use App\Models\PaymentIntent;
use Illuminate\Validation\ValidationException;

final class PaymentStateMachine
{
    private const TRANSITIONS = [
        'created' => ['processing', 'requires_action', 'failed', 'cancelled', 'expired', 'unknown'],
        'requires_action' => ['processing', 'failed', 'cancelled', 'expired', 'unknown'],
        'processing' => ['succeeded', 'failed', 'returned', 'unknown'],
        'unknown' => ['processing', 'succeeded', 'failed', 'returned'],
        'succeeded' => [],
        'failed' => [],
        'returned' => [],
        'cancelled' => [],
        'expired' => [],
    ];

    public function transition(PaymentIntent $intent, string $to, ?string $reason = null): PaymentIntent
    {
        if ($intent->status === $to) return $intent;
        if (!in_array($to, self::TRANSITIONS[$intent->status] ?? [], true)) throw ValidationException::withMessages(['status' => "Payment cannot move from {$intent->status} to {$to}."]);
        $intent->update(['status' => $to, 'failure_reason' => in_array($to, ['failed', 'returned'], true) ? $reason : $intent->failure_reason, 'completed_at' => in_array($to, ['succeeded', 'failed', 'returned', 'cancelled', 'expired'], true) ? now() : null]);
        return $intent->refresh();
    }
}
