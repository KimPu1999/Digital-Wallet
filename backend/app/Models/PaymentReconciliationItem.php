<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReconciliationItem extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['payment_intent_id', 'provider_transaction_id', 'settlement_item_id', 'exception_type', 'status', 'expected_minor', 'actual_minor', 'difference_minor', 'reason', 'metadata'];
    protected $casts = ['expected_minor' => 'integer', 'actual_minor' => 'integer', 'difference_minor' => 'integer', 'metadata' => 'array'];
    public function intent(): BelongsTo { return $this->belongsTo(PaymentIntent::class, 'payment_intent_id'); }
    public function providerTransaction(): BelongsTo { return $this->belongsTo(ProviderTransaction::class); }
    public function settlementItem(): BelongsTo { return $this->belongsTo(SettlementItem::class); }
}
