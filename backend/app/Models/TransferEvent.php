<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TransferEvent extends Model { use HasUuids; public $timestamps=false; public $incrementing=false; protected $keyType='string'; protected $fillable=['transfer_id','from_status','to_status','event_type','actor_id','reason','metadata','created_at']; protected $casts=['metadata'=>'array','created_at'=>'datetime']; public function transfer(): BelongsTo { return $this->belongsTo(Transfer::class); } }
