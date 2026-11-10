<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundRequest extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['merchant_id', 'payment_intent_id', 'requested_by', 'approved_by', 'ledger_transaction_id', 'idempotency_key', 'amount_minor', 'currency_code', 'reason', 'status', 'provider_reference', 'approved_at', 'completed_at', 'metadata'];
    protected $casts = ['amount_minor' => 'integer', 'approved_at' => 'datetime', 'completed_at' => 'datetime', 'metadata' => 'array'];
    public function merchant(): BelongsTo { return $this->belongsTo(Merchant::class); }
    public function paymentIntent(): BelongsTo { return $this->belongsTo(PaymentIntent::class, 'payment_intent_id'); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function ledgerTransaction(): BelongsTo { return $this->belongsTo(LedgerTransaction::class); }
}
