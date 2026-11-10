<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LedgerAccount extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['wallet_currency_account_id', 'currency_code', 'code', 'name', 'category', 'normal_side', 'status', 'restricted'];
    protected $casts = ['restricted' => 'boolean'];

    public function walletCurrencyAccount(): BelongsTo { return $this->belongsTo(WalletCurrencyAccount::class); }
    public function entries(): HasMany { return $this->hasMany(LedgerEntry::class); }
}
