<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantPaymentLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount_minor' => $this->amount_minor,
            'currency_code' => $this->currency_code,
            'description' => $this->description,
            'status' => $this->status,
            'expires_at' => $this->expires_at,
            'usage_limit' => $this->usage_limit,
            'usage_count' => $this->usage_count,
            'single_use' => $this->single_use,
            'sandbox_only' => true,
            'created_at' => $this->created_at,
        ];
    }
}
