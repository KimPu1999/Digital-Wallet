<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class MfaRecoveryCode extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['user_id','code_hash','used_at']; protected $casts=['used_at'=>'datetime']; public function user(): BelongsTo { return $this->belongsTo(User::class); } }
