<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementItem extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['settlement_batch_id', 'payment_intent_id', 'provider_transaction_id', 'ledger_transaction_id', 'currency_code', 'amount_minor', 'fee_minor', 'status', 'metadata'];
    protected $casts = ['amount_minor' => 'integer', 'fee_minor' => 'integer', 'metadata' => 'array'];
    public function batch(): BelongsTo { return $this->belongsTo(SettlementBatch::class, 'settlement_batch_id'); }
    public function intent(): BelongsTo { return $this->belongsTo(PaymentIntent::class, 'payment_intent_id'); }
    public function providerTransaction(): BelongsTo { return $this->belongsTo(ProviderTransaction::class); }
    public function ledgerTransaction(): BelongsTo { return $this->belongsTo(LedgerTransaction::class); }
}
