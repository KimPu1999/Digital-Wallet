<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class AdminSecurityEvent extends Model { use HasUuids; public $incrementing=false; public $timestamps=false; protected $keyType='string'; protected $fillable=['actor_id','event_type','correlation_id','ip_address','user_agent','metadata','created_at']; protected $casts=['metadata'=>'array','created_at'=>'datetime']; }
