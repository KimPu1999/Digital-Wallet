<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class NotificationDelivery extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['notification_id','user_id','channel','status','attempts','next_attempt_at','delivered_at','provider_reference','failure_reason','metadata']; protected $casts=['next_attempt_at'=>'datetime','delivered_at'=>'datetime','metadata'=>'array']; public function notification(): BelongsTo { return $this->belongsTo(Notification::class); } }
