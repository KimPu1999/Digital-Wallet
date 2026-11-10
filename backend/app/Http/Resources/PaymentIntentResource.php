<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentIntentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'intent_reference' => $this->intent_reference,
            'operation' => $this->operation,
            'payment_method' => $this->payment_method,
            'currency_code' => $this->currency_code,
            'amount_minor' => (int) $this->amount_minor,
            'fee_minor' => (int) $this->fee_minor,
            'status' => $this->status,
            'failure_reason' => $this->failure_reason,
            'external_reference' => $this->external_reference,
            'settlement_reference' => $this->settlement_reference,
            'expires_at' => $this->expires_at,
            'completed_at' => $this->completed_at,
            'sandbox_only' => (bool) $this->sandbox_only,
            'provider' => $this->whenLoaded('provider', fn () => ['code' => $this->provider?->code, 'name' => $this->provider?->name]),
            'attempts' => $this->whenLoaded('attempts', fn () => $this->attempts->map(fn ($attempt) => ['id' => $attempt->id, 'status' => $attempt->status, 'provider_reference' => $attempt->provider_reference, 'failure_reason' => $attempt->failure_reason, 'started_at' => $attempt->started_at, 'completed_at' => $attempt->completed_at])),
        ];
    }
}
