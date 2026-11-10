<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletHold extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['wallet_currency_account_id', 'currency_code', 'amount_minor', 'status', 'reference', 'expires_at', 'created_by', 'idempotency_key_id', 'captured_at', 'released_at', 'metadata'];
    protected $casts = ['amount_minor' => 'integer', 'expires_at' => 'datetime', 'captured_at' => 'datetime', 'released_at' => 'datetime', 'metadata' => 'array'];
    public function walletCurrencyAccount(): BelongsTo { return $this->belongsTo(WalletCurrencyAccount::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
