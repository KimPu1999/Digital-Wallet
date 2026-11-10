<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LedgerAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'code' => $this->code, 'name' => $this->name, 'category' => $this->category, 'normal_side' => $this->normal_side, 'currency_code' => $this->currency_code, 'status' => $this->status, 'wallet_currency_account_id' => $this->wallet_currency_account_id, 'restricted' => $this->restricted];
    }
}
