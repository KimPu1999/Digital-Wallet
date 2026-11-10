<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LedgerTransaction extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['reference', 'transaction_type', 'status', 'idempotency_key_id', 'actor_id', 'description', 'metadata', 'posted_at'];
    protected $casts = ['metadata' => 'array', 'posted_at' => 'datetime'];

    public function entries(): HasMany { return $this->hasMany(LedgerEntry::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
    public function idempotencyKey(): BelongsTo { return $this->belongsTo(IdempotencyKey::class); }
}
