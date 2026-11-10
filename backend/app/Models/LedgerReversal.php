<?php

namespace App\Models;

use LogicException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerReversal extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['original_transaction_id', 'reversal_transaction_id', 'actor_id', 'reason'];
    public function original(): BelongsTo { return $this->belongsTo(LedgerTransaction::class, 'original_transaction_id'); }
    public function reversal(): BelongsTo { return $this->belongsTo(LedgerTransaction::class, 'reversal_transaction_id'); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
    protected static function booted(): void { static::updating(fn () => throw new LogicException('Ledger reversals are immutable.')); static::deleting(fn () => throw new LogicException('Ledger reversals are immutable.')); }
}
