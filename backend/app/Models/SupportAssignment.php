<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class SupportAssignment extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; public $timestamps=false; protected $fillable=['support_ticket_id','agent_id','assigned_by','assigned_at','unassigned_at']; protected $casts=['assigned_at'=>'datetime','unassigned_at'=>'datetime']; }
