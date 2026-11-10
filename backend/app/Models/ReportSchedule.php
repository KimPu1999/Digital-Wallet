<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class ReportSchedule extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['report_definition_id','created_by','frequency','parameters','recipients','status','next_run_at','last_run_at']; protected $casts=['parameters'=>'array','recipients'=>'array','next_run_at'=>'datetime','last_run_at'=>'datetime']; }
