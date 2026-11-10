<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class BeneficiaryResource extends JsonResource { public function toArray($request): array { return ['id'=>$this->id,'display_name'=>$this->display_name,'beneficiary_type'=>$this->beneficiary_type,'country_code'=>$this->country_code,'status'=>$this->status,'verification_status'=>$this->verification_status,'risk_status'=>$this->risk_status,'accounts'=>$this->whenLoaded('accounts',fn()=>BeneficiaryAccountResource::collection($this->accounts)),'sandbox_only'=>true]; } }
