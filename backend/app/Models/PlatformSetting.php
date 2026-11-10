<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class PlatformSetting extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['setting_key','value','version','critical']; protected $casts=['value'=>'array','version'=>'integer','critical'=>'boolean']; }
