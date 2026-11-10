<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentProvider extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['code', 'name', 'provider_type', 'environment', 'status', 'enabled', 'supported_countries', 'supported_currencies', 'supported_payment_methods', 'supported_operations', 'health_status', 'timeout_ms', 'fee_metadata', 'metadata'];
    protected $casts = ['enabled' => 'boolean', 'supported_countries' => 'array', 'supported_currencies' => 'array', 'supported_payment_methods' => 'array', 'supported_operations' => 'array', 'fee_metadata' => 'array', 'metadata' => 'array'];
    public function accounts(): HasMany { return $this->hasMany(ProviderAccount::class, 'provider_id'); }
    public function capabilities(): HasMany { return $this->hasMany(ProviderCapability::class, 'provider_id'); }
    public function routingRules(): HasMany { return $this->hasMany(ProviderRoutingRule::class, 'provider_id'); }
    public function healthEvents(): HasMany { return $this->hasMany(ProviderHealthEvent::class, 'provider_id'); }
}
