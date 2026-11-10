<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class FxConversionResource extends JsonResource { public function toArray($request): array { return ['id'=>$this->id,'quote_id'=>$this->quote_id,'source_currency_code'=>$this->source_currency_code,'target_currency_code'=>$this->target_currency_code,'source_amount_minor'=>(int)$this->source_amount_minor,'target_amount_minor'=>(int)$this->target_amount_minor,'fee_minor'=>(int)$this->fee_minor,'exchange_rate'=>(string)$this->exchange_rate,'status'=>$this->status,'ledger_transaction_id'=>$this->ledger_transaction_id,'completed_at'=>$this->completed_at,'sandbox_only'=>(bool)$this->sandbox_only,'legs'=>$this->whenLoaded('legs',fn()=>FxConversionLegResource::collection($this->legs))]; } }
