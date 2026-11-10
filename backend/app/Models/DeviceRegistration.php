<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class DeviceRegistration extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['user_id','device_key_hash','label','platform','status','last_seen_at','registered_at','metadata']; protected $casts=['last_seen_at'=>'datetime','registered_at'=>'datetime','metadata'=>'array']; protected $hidden=['device_key_hash']; public function user(): BelongsTo { return $this->belongsTo(User::class); } }
