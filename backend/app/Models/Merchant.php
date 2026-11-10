<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Merchant extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['legal_name', 'display_name', 'status'];

    public function memberships(): HasMany { return $this->hasMany(MerchantMembership::class); }
    public function paymentLinks(): HasMany { return $this->hasMany(MerchantPaymentLink::class); }
    public function paymentIntents(): HasMany { return $this->hasMany(PaymentIntent::class); }
    public function settlements(): HasMany { return $this->hasMany(MerchantSettlement::class); }
    public function checkoutSettings(): HasOne { return $this->hasOne(MerchantCheckoutSetting::class); }
}
