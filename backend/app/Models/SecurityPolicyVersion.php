<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class SecurityPolicyVersion extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['policy_key','version','status','configuration','published_by','published_at']; protected $casts=['configuration'=>'array','published_at'=>'datetime']; }
