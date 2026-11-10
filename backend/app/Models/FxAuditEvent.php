<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class FxAuditEvent extends Model { use HasUuids; public $incrementing=false; public $timestamps=false; protected $keyType='string'; protected $fillable=['actor_id','quote_id','conversion_id','action','metadata','created_at']; protected $casts=['metadata'=>'array','created_at'=>'datetime']; }
