<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TransferAttempt extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['transfer_id','attempt_number','provider','status','provider_reference','failure_reason','started_at','completed_at','metadata']; protected $casts=['started_at'=>'datetime','completed_at'=>'datetime','metadata'=>'array']; public function transfer(): BelongsTo { return $this->belongsTo(Transfer::class); } }
