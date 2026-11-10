<?php
namespace App\Http\Resources;
use Illuminate\Http\Request; use Illuminate\Http\Resources\Json\JsonResource;
class WalletCurrencyAccountResource extends JsonResource { public function toArray(Request $request): array { return ['id'=>$this->id,'status'=>$this->status,'currency'=>['code'=>$this->currency?->code,'name'=>$this->currency?->name,'symbol'=>$this->currency?->symbol,'exponent'=>$this->currency?->decimals,'enabled'=>$this->currency?->is_active],'balance'=>null,'balance_label'=>'Available after Phase 4 ledger']; } }
