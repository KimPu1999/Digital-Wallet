<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class PlatformAuditEvent extends Model { use HasUuids; public $incrementing=false; public $timestamps=false; protected $keyType='string'; protected $fillable=['actor_id','action','resource_type','resource_id','correlation_id','decision','approval_request_id','previous_state','new_state','metadata','created_at']; protected $casts=['previous_state'=>'array','new_state'=>'array','metadata'=>'array','created_at'=>'datetime']; }
