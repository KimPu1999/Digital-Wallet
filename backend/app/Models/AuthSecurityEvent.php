<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AuthSecurityEvent extends Model { use HasUuids; public $incrementing=false; public $timestamps=false; protected $keyType='string'; protected $fillable=['user_id','event_type','severity','ip_address','device_key_hash','correlation_id','metadata','occurred_at']; protected $casts=['metadata'=>'array','occurred_at'=>'datetime']; protected $hidden=['device_key_hash']; public function user(): BelongsTo { return $this->belongsTo(User::class); } }
