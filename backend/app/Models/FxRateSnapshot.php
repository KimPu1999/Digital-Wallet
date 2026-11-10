<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FxRateSnapshot extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['provider_id','pair_id','rate','inverse_rate','observed_at','expires_at','provider_reference','status','metadata']; protected $casts=['observed_at'=>'datetime','expires_at'=>'datetime','metadata'=>'array']; public function pair(): BelongsTo { return $this->belongsTo(FxPair::class,'pair_id'); } public function provider(): BelongsTo { return $this->belongsTo(FxRateProvider::class,'provider_id'); } }
