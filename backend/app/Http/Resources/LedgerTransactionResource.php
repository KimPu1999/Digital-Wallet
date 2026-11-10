<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LedgerTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'reference' => $this->reference, 'transaction_type' => $this->transaction_type, 'status' => $this->status, 'description' => $this->description, 'posted_at' => $this->posted_at, 'entries' => LedgerEntryResource::collection($this->whenLoaded('entries')), 'sandbox_only' => true];
    }
}
