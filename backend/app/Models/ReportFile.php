<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ReportFile extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['report_run_id','format','storage_key','checksum','row_count','status','created_by','expires_at']; protected $casts=['row_count'=>'integer','expires_at'=>'datetime']; public function run(): BelongsTo { return $this->belongsTo(ReportRun::class,'report_run_id'); } }
