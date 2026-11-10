<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ReportRun extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['report_definition_id','requested_by','scope','status','parameters','result_summary','result_checksum','error_message','started_at','completed_at','expires_at']; protected $casts=['parameters'=>'array','result_summary'=>'array','started_at'=>'datetime','completed_at'=>'datetime','expires_at'=>'datetime']; public function files(): HasMany { return $this->hasMany(ReportFile::class); } }
