<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class BeneficiaryAccountResource extends JsonResource { public function toArray($request): array { return ['id'=>$this->id,'account_type'=>$this->account_type,'currency_code'=>$this->currency_code,'account_last4'=>$this->account_last4,'status'=>$this->status]; } }
