<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany;
class KycCase extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['user_id','status','country_code','submitted_at','reviewed_at']; protected $casts=['submitted_at'=>'datetime','reviewed_at'=>'datetime']; public function user(): BelongsTo { return $this->belongsTo(User::class); } public function documents(): HasMany { return $this->hasMany(KycDocument::class); } }
