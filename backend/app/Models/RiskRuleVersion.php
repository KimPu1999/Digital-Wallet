<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class RiskRuleVersion extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['risk_rule_id','version','configuration','status','published_by','published_at']; protected $casts=['configuration'=>'array','published_at'=>'datetime']; public function rule(): BelongsTo { return $this->belongsTo(RiskRule::class,'risk_rule_id'); } }
