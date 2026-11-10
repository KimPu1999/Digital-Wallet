<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWebhookEvent extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['provider_id', 'event_id', 'event_type', 'signature_valid', 'status', 'raw_body_encrypted', 'received_at', 'event_occurred_at', 'processed_at', 'error_message', 'metadata'];
    protected $casts = ['signature_valid' => 'boolean', 'received_at' => 'datetime', 'event_occurred_at' => 'datetime', 'processed_at' => 'datetime', 'metadata' => 'array'];
    protected $hidden = ['raw_body_encrypted'];
    public function provider(): BelongsTo { return $this->belongsTo(PaymentProvider::class, 'provider_id'); }
}
