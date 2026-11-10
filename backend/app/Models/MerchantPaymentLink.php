<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchantPaymentLink extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['merchant_id', 'created_by', 'public_token_hash', 'amount_minor', 'currency_code', 'description', 'status', 'expires_at', 'usage_limit', 'usage_count', 'single_use', 'metadata'];
    protected $casts = ['amount_minor' => 'integer', 'expires_at' => 'datetime', 'usage_limit' => 'integer', 'usage_count' => 'integer', 'single_use' => 'boolean', 'metadata' => 'array'];
    public function merchant(): BelongsTo { return $this->belongsTo(Merchant::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function checkoutSessions(): HasMany { return $this->hasMany(CheckoutSession::class, 'payment_link_id'); }
}
