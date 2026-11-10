<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class RiskEvaluation extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['subject_type','subject_id','risk_rule_version_id','decision','evaluation_key','evidence','failure_reason']; protected $casts=['evidence'=>'array']; public function ruleVersion(): BelongsTo { return $this->belongsTo(RiskRuleVersion::class,'risk_rule_version_id'); } }
