<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderAccount extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['provider_id', 'account_key', 'environment', 'encrypted_credentials', 'status', 'metadata'];
    protected $casts = ['metadata' => 'array'];
    protected $hidden = ['encrypted_credentials'];
    public function provider(): BelongsTo { return $this->belongsTo(PaymentProvider::class, 'provider_id'); }
}
