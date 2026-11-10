<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LedgerEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'currency_code' => $this->currency_code,
            'side' => $this->side,
            'amount_minor' => $this->amount_minor,
            'sequence' => $this->sequence,
            'account_code' => $this->whenLoaded('account', fn () => $this->account->code),
            'transaction' => $this->whenLoaded('transaction', fn () => [
                'reference' => $this->transaction->reference,
                'type' => $this->transaction->transaction_type,
                'status' => $this->transaction->status,
                'description' => $this->transaction->description,
                'posted_at' => $this->transaction->posted_at,
                'created_at' => $this->transaction->created_at,
            ]),
        ];
    }
}
