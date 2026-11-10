<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class ReportAuditEvent extends Model { use HasUuids; public $incrementing=false; public $timestamps=false; protected $keyType='string'; protected $fillable=['report_run_id','actor_id','action','metadata','created_at']; protected $casts=['metadata'=>'array','created_at'=>'datetime']; }
