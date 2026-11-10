<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantQrResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchant_id' => $this->merchant_id,
            'checkout_session_id' => $this->checkout_session_id,
            'qr_reference' => $this->qr_reference,
            'qr_type' => $this->qr_type,
            'amount_minor' => $this->amount_minor,
            'currency_code' => $this->currency_code,
            'status' => $this->status,
            'expires_at' => $this->expires_at,
            'sandbox_only' => true,
        ];
    }
}
