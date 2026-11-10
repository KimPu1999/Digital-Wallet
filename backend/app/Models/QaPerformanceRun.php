<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class QaPerformanceRun extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['qa_test_run_id','target','requests_per_second','p50_ms','p95_ms','p99_ms','error_rate','status','metrics']; protected $casts=['requests_per_second'=>'integer','p50_ms'=>'decimal:3','p95_ms'=>'decimal:3','p99_ms'=>'decimal:3','error_rate'=>'decimal:5','metrics'=>'array']; }
