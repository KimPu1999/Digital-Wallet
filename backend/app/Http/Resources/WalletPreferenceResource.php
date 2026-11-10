<?php
namespace App\Http\Resources;
use Illuminate\Http\Request; use Illuminate\Http\Resources\Json\JsonResource;
class WalletPreferenceResource extends JsonResource { public function toArray(Request $request): array { return ['preferred_currency'=>$this->preferredCurrency?->code,'locale'=>$this->locale]; } }
