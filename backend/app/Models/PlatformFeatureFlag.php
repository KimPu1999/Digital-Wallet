<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class PlatformFeatureFlag extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['flag_key','enabled','environment','category','rollout_percentage','metadata','targeting','scheduled_at','expires_at']; protected $casts=['enabled'=>'boolean','rollout_percentage'=>'integer','metadata'=>'array','targeting'=>'array','scheduled_at'=>'datetime','expires_at'=>'datetime']; }
