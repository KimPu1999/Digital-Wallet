<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchant_id' => $this->merchant_id,
            'order_reference' => $this->order_reference,
            'amount_minor' => $this->amount_minor,
            'currency_code' => $this->currency_code,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'expires_at' => $this->expires_at,
            'allowed_payment_methods' => $this->allowed_payment_methods ?? [],
            'branding' => $this->branding_snapshot ?? [],
            'success_url' => $this->success_url,
            'cancel_url' => $this->cancel_url,
            'sandbox_only' => true,
        ];
    }
}
