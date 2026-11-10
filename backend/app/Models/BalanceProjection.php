<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceProjection extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['wallet_currency_account_id', 'currency_code', 'posted_minor', 'held_minor', 'version', 'last_posted_at'];
    protected $casts = ['posted_minor' => 'integer', 'held_minor' => 'integer', 'version' => 'integer', 'last_posted_at' => 'datetime'];
    public function walletCurrencyAccount(): BelongsTo { return $this->belongsTo(WalletCurrencyAccount::class); }
}
