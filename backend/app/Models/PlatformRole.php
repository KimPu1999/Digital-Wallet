<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsToMany; use Illuminate\Database\Eloquent\Relations\HasMany;
class PlatformRole extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['name','guard_name']; public function permissions(): BelongsToMany { return $this->belongsToMany(PlatformPermission::class,'platform_role_permissions'); } public function memberships(): HasMany { return $this->hasMany(PlatformAdminMembership::class); } }
