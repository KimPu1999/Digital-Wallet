<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class FxConversionLegResource extends JsonResource { public function toArray($request): array { return ['currency_code'=>$this->currency_code,'leg_type'=>$this->leg_type,'side'=>$this->side,'amount_minor'=>(int)$this->amount_minor,'sequence'=>(int)$this->sequence,'ledger_account_id'=>$this->ledger_account_id]; } }
