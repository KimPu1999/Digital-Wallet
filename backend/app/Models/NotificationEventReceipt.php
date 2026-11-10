<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class NotificationEventReceipt extends Model { use HasUuids; public $incrementing=false; public $timestamps=false; protected $keyType='string'; protected $fillable=['event_key','event_type','user_id','notification_id','processed_at','metadata']; protected $casts=['processed_at'=>'datetime','metadata'=>'array']; }
