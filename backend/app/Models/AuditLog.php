<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AuditLog extends Model { public $incrementing=false; protected $keyType='string'; protected $fillable=['user_id','action','metadata','ip_address']; protected $casts=['metadata'=>'array']; }
