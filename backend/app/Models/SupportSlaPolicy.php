<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class SupportSlaPolicy extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['policy_key','version','priority','response_minutes','resolution_minutes','status','business_hours']; protected $casts=['business_hours'=>'array']; }
