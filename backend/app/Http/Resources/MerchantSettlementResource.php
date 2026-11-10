<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantSettlementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'settlement_reference' => $this->settlement_reference,
            'currency_code' => $this->currency_code,
            'gross_amount_minor' => $this->gross_amount_minor,
            'fee_amount_minor' => $this->fee_amount_minor,
            'refund_amount_minor' => $this->refund_amount_minor,
            'net_amount_minor' => $this->net_amount_minor,
            'status' => $this->status,
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'settled_at' => $this->settled_at,
            'sandbox_only' => true,
        ];
    }
}
