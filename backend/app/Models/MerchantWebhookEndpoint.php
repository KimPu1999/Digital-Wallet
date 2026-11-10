<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchantWebhookEndpoint extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['merchant_id', 'created_by', 'url', 'signing_secret_encrypted', 'events', 'status', 'last_delivery_at'];
    protected $casts = ['events' => 'array', 'last_delivery_at' => 'datetime'];
    protected $hidden = ['signing_secret_encrypted'];
    public function merchant(): BelongsTo { return $this->belongsTo(Merchant::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function deliveries(): HasMany { return $this->hasMany(MerchantWebhookDelivery::class, 'merchant_webhook_endpoint_id'); }
}
