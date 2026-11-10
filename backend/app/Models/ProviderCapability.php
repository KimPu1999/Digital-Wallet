<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderCapability extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['provider_id', 'operation', 'country_code', 'currency_code', 'payment_method', 'is_supported', 'metadata'];
    protected $casts = ['is_supported' => 'boolean', 'metadata' => 'array'];
    public function provider(): BelongsTo { return $this->belongsTo(PaymentProvider::class, 'provider_id'); }
}
