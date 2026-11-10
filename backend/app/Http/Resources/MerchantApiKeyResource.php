<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantApiKeyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'key_prefix' => $this->key_prefix,
            'scopes' => $this->scopes ?? [],
            'expires_at' => $this->expires_at,
            'last_used_at' => $this->last_used_at,
            'revoked_at' => $this->revoked_at,
            'sandbox_only' => true,
        ];
    }
}
