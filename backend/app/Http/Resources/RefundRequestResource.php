<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_intent_id' => $this->payment_intent_id,
            'amount_minor' => $this->amount_minor,
            'currency_code' => $this->currency_code,
            'reason' => $this->reason,
            'status' => $this->status,
            'requested_by' => $this->requested_by,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'completed_at' => $this->completed_at,
            'sandbox_only' => true,
        ];
    }
}
