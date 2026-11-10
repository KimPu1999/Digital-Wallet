<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasMany;
class RiskCase extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['subject_type','subject_id','opened_by','assigned_to','status','severity','reason','metadata']; protected $casts=['metadata'=>'array']; public function events(): HasMany { return $this->hasMany(RiskCaseEvent::class); } }
