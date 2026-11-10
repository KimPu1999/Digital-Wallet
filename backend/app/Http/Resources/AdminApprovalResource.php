<?php
namespace App\Http\Resources;
use Illuminate\Http\Request; use Illuminate\Http\Resources\Json\JsonResource;
class AdminApprovalResource extends JsonResource { public function toArray(Request $request): array { return ['id'=>$this->id,'action'=>$this->action,'resource_type'=>$this->resource_type,'resource_id'=>$this->resource_id,'status'=>$this->status,'required_decisions'=>$this->required_decisions,'expires_at'=>$this->expires_at,'reason'=>$this->reason,'requested_by'=>$this->requested_by,'decisions'=>$this->whenLoaded('decisions'),'sandbox_only'=>true]; } }
