<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class NotificationTemplate extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['template_key','locale','version','subject','email_body','sms_body','push_title','push_body','in_app_body','mandatory','status']; protected $casts=['mandatory'=>'boolean']; }
