<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class DisputeEvent extends Model { use HasUuids; public $incrementing=false; public $timestamps=false; protected $keyType='string'; protected $fillable=['dispute_case_id','actor_id','event_type','from_status','to_status','notes','metadata','created_at']; protected $casts=['metadata'=>'array','created_at'=>'datetime']; public function dispute(): BelongsTo { return $this->belongsTo(DisputeCase::class,'dispute_case_id'); } }
