<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasMany;
class ScreeningRequest extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['subject_type','subject_id','provider','status','provider_reference','request_key','requested_at','completed_at','metadata']; protected $casts=['requested_at'=>'datetime','completed_at'=>'datetime','metadata'=>'array']; public function matches(): HasMany { return $this->hasMany(ScreeningMatch::class); } }
