<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantWebhookEndpointResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'events' => $this->events ?? [],
            'status' => $this->status,
            'last_delivery_at' => $this->last_delivery_at,
            'sandbox_only' => true,
        ];
    }
}
