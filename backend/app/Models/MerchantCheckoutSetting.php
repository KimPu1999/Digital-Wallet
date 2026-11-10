<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantCheckoutSetting extends Model
{
    protected $fillable = ['merchant_id', 'branding', 'allowed_currencies', 'allowed_payment_methods', 'allowed_redirect_hosts', 'default_success_url', 'default_cancel_url'];
    protected $casts = ['branding' => 'array', 'allowed_currencies' => 'array', 'allowed_payment_methods' => 'array', 'allowed_redirect_hosts' => 'array'];
    public function merchant(): BelongsTo { return $this->belongsTo(Merchant::class); }
}
