<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutSession extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['merchant_id', 'payment_link_id', 'payment_intent_id', 'public_token_hash', 'order_reference', 'amount_minor', 'currency_code', 'status', 'payment_method', 'customer_email', 'expires_at', 'success_url', 'cancel_url', 'allowed_payment_methods', 'branding_snapshot', 'metadata'];
    protected $casts = ['amount_minor' => 'integer', 'expires_at' => 'datetime', 'allowed_payment_methods' => 'array', 'branding_snapshot' => 'array', 'metadata' => 'array'];
    public function merchant(): BelongsTo { return $this->belongsTo(Merchant::class); }
    public function paymentLink(): BelongsTo { return $this->belongsTo(MerchantPaymentLink::class, 'payment_link_id'); }
    public function paymentIntent(): BelongsTo { return $this->belongsTo(PaymentIntent::class, 'payment_intent_id'); }
    public function isExpired(): bool { return now()->greaterThanOrEqualTo($this->expires_at); }
}
