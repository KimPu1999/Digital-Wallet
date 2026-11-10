<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantApiKey extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['merchant_id', 'created_by', 'name', 'key_prefix', 'key_hash', 'scopes', 'expires_at', 'last_used_at', 'revoked_at'];
    protected $casts = ['scopes' => 'array', 'expires_at' => 'datetime', 'last_used_at' => 'datetime', 'revoked_at' => 'datetime'];
    protected $hidden = ['key_hash'];
    public function merchant(): BelongsTo { return $this->belongsTo(Merchant::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function isUsable(): bool { return $this->revoked_at === null && ($this->expires_at === null || $this->expires_at->isFuture()); }
}
