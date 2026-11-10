<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantQrCode extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['merchant_id', 'checkout_session_id', 'qr_reference', 'token_hash', 'qr_type', 'amount_minor', 'currency_code', 'status', 'expires_at', 'payload'];
    protected $casts = ['amount_minor' => 'integer', 'expires_at' => 'datetime', 'payload' => 'array'];
    public function merchant(): BelongsTo { return $this->belongsTo(Merchant::class); }
    public function checkoutSession(): BelongsTo { return $this->belongsTo(CheckoutSession::class); }
}
