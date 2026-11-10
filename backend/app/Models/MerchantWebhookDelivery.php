<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantWebhookDelivery extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['merchant_webhook_endpoint_id', 'event_id', 'event_type', 'payload_encrypted', 'status', 'attempts', 'response_status', 'next_attempt_at', 'delivered_at', 'error_message'];
    protected $casts = ['next_attempt_at' => 'datetime', 'delivered_at' => 'datetime'];
    protected $hidden = ['payload_encrypted'];
    public function endpoint(): BelongsTo { return $this->belongsTo(MerchantWebhookEndpoint::class, 'merchant_webhook_endpoint_id'); }
}
