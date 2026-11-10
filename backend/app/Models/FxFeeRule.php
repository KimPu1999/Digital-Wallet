<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FxFeeRule extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['pair_id','fixed_fee_minor','percentage_bps','provider_spread_bps','platform_markup_bps','minimum_fee_minor','maximum_fee_minor','is_enabled']; protected $casts=['is_enabled'=>'boolean']; public function pair(): BelongsTo { return $this->belongsTo(FxPair::class,'pair_id'); } }
