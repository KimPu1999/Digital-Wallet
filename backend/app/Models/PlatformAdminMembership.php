<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PlatformAdminMembership extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['user_id','platform_role_id','status','last_step_up_at']; protected $casts=['last_step_up_at'=>'datetime']; public function user(): BelongsTo { return $this->belongsTo(User::class); } public function role(): BelongsTo { return $this->belongsTo(PlatformRole::class,'platform_role_id'); } }
