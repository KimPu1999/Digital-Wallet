<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class RiskCaseEvent extends Model { use HasUuids; public $incrementing=false; public $timestamps=false; protected $keyType='string'; protected $fillable=['risk_case_id','actor_id','event_type','notes','metadata','created_at']; protected $casts=['metadata'=>'array','created_at'=>'datetime']; }
