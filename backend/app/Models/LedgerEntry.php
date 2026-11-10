<?php

namespace App\Models;

use LogicException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    use HasUuids;

    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';
    protected $fillable = ['ledger_transaction_id', 'ledger_account_id', 'currency_code', 'side', 'amount_minor', 'sequence', 'metadata', 'created_at'];
    protected $casts = ['metadata' => 'array', 'amount_minor' => 'integer', 'sequence' => 'integer', 'created_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Ledger entries are immutable.'));
        static::deleting(fn () => throw new LogicException('Ledger entries are immutable.'));
    }

    public function transaction(): BelongsTo { return $this->belongsTo(LedgerTransaction::class, 'ledger_transaction_id'); }
    public function account(): BelongsTo { return $this->belongsTo(LedgerAccount::class, 'ledger_account_id'); }
}
