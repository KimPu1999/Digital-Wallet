<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentIntent extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['user_id', 'merchant_id', 'wallet_currency_account_id', 'operation', 'payment_method', 'currency_code', 'amount_minor', 'fee_minor', 'provider_id', 'provider_account_id', 'idempotency_key_id', 'intent_reference', 'external_reference', 'status', 'failure_reason', 'settlement_reference', 'expires_at', 'completed_at', 'sandbox_only', 'metadata'];
    protected $casts = ['amount_minor' => 'integer', 'fee_minor' => 'integer', 'expires_at' => 'datetime', 'completed_at' => 'datetime', 'sandbox_only' => 'boolean', 'metadata' => 'array'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function merchant(): BelongsTo { return $this->belongsTo(Merchant::class); }
    public function walletCurrencyAccount(): BelongsTo { return $this->belongsTo(WalletCurrencyAccount::class); }
    public function provider(): BelongsTo { return $this->belongsTo(PaymentProvider::class); }
    public function providerAccount(): BelongsTo { return $this->belongsTo(ProviderAccount::class); }
    public function attempts(): HasMany { return $this->hasMany(PaymentAttempt::class); }
    public function providerTransactions(): HasMany { return $this->hasMany(ProviderTransaction::class); }
    public function webhookEvents(): HasMany { return $this->hasMany(PaymentWebhookEvent::class); }
    public function settlementItems(): HasMany { return $this->hasMany(SettlementItem::class); }
}
