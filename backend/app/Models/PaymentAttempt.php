<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentAttempt extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['payment_intent_id', 'provider_id', 'attempt_number', 'status', 'provider_reference', 'failure_reason', 'started_at', 'completed_at', 'metadata'];
    protected $casts = ['started_at' => 'datetime', 'completed_at' => 'datetime', 'metadata' => 'array'];
    public function intent(): BelongsTo { return $this->belongsTo(PaymentIntent::class, 'payment_intent_id'); }
    public function provider(): BelongsTo { return $this->belongsTo(PaymentProvider::class, 'provider_id'); }
    public function providerTransactions(): HasMany { return $this->hasMany(ProviderTransaction::class); }
}
