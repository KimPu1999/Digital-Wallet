<?php
namespace App\Http\Resources;
use Illuminate\Http\Request; use Illuminate\Http\Resources\Json\JsonResource;
class RiskCaseResource extends JsonResource { public function toArray(Request $request): array { return ['id'=>$this->id,'subject_type'=>$this->subject_type,'subject_id'=>$this->subject_id,'status'=>$this->status,'severity'=>$this->severity,'reason'=>$this->reason,'assigned_to'=>$this->assigned_to,'events'=>$this->whenLoaded('events'),'created_at'=>$this->created_at,'sandbox_only'=>true]; } }
