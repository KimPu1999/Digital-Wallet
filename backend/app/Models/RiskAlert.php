<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class RiskAlert extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['risk_evaluation_id','subject_type','subject_id','category','severity','status','deduplication_key','reason','evidence','assigned_to']; protected $casts=['evidence'=>'array']; public function evaluation(): BelongsTo { return $this->belongsTo(RiskEvaluation::class,'risk_evaluation_id'); } }
