<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasMany;
class RiskRule extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['rule_key','name','category','default_decision','enabled','description']; protected $casts=['enabled'=>'boolean']; public function versions(): HasMany { return $this->hasMany(RiskRuleVersion::class); } }
