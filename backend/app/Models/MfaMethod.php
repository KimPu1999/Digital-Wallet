<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class MfaMethod extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['user_id','type','secret_encrypted','is_primary','verified_at']; protected $casts=['is_primary'=>'boolean','verified_at'=>'datetime']; public function user(): BelongsTo { return $this->belongsTo(User::class); } }
