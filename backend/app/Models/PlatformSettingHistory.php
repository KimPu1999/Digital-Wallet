<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class PlatformSettingHistory extends Model { use HasUuids; public $incrementing=false; public $timestamps=false; protected $keyType='string'; protected $fillable=['setting_key','version','value','changed_by','approval_request_id','created_at']; protected $casts=['value'=>'array','version'=>'integer','created_at'=>'datetime']; }
