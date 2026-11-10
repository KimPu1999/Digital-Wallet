<?php

namespace App\Services;

use App\Models\PaymentIntent;
use App\Models\PaymentReconciliationItem;
use App\Models\ProviderTransaction;
use Illuminate\Database\Eloquent\Builder;

final class PaymentReconciliationService
{
    public function exception(?PaymentIntent $intent, ?ProviderTransaction $providerTransaction, string $type, string $reason, ?int $expected, ?int $actual): PaymentReconciliationItem
    {
        $query = PaymentReconciliationItem::query()->where('exception_type', $type)->where('reason', $reason);
        if ($intent) $query->where('payment_intent_id', $intent->id);
        if ($providerTransaction) $query->where('provider_transaction_id', $providerTransaction->id);
        $existing = $query->first();
        if ($existing) return $existing;
        return PaymentReconciliationItem::create(['payment_intent_id' => $intent?->id, 'provider_transaction_id' => $providerTransaction?->id, 'exception_type' => $type, 'status' => 'open', 'expected_minor' => $expected, 'actual_minor' => $actual, 'difference_minor' => $expected !== null && $actual !== null ? $actual - $expected : null, 'reason' => $reason, 'metadata' => ['sandbox_only' => true]]);
    }

    public function query(): Builder
    {
        return PaymentReconciliationItem::with(['intent', 'providerTransaction'])->latest();
    }
}
