<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class AuditLog extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['user_id','action','metadata','ip_address']; protected $casts=['metadata'=>'array']; }
