<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderTransaction extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['payment_attempt_id', 'payment_intent_id', 'provider_id', 'provider_event_id', 'external_reference', 'status', 'currency_code', 'amount_minor', 'fee_minor', 'observed_at', 'raw_payload', 'metadata'];
    protected $casts = ['amount_minor' => 'integer', 'fee_minor' => 'integer', 'observed_at' => 'datetime', 'raw_payload' => 'array', 'metadata' => 'array'];
    public function intent(): BelongsTo { return $this->belongsTo(PaymentIntent::class, 'payment_intent_id'); }
    public function attempt(): BelongsTo { return $this->belongsTo(PaymentAttempt::class, 'payment_attempt_id'); }
    public function provider(): BelongsTo { return $this->belongsTo(PaymentProvider::class, 'provider_id'); }
}
