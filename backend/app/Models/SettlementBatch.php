<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SettlementBatch extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['provider_id', 'settlement_reference', 'currency_code', 'status', 'total_amount_minor', 'total_fee_minor', 'expected_at', 'settled_at', 'metadata'];
    protected $casts = ['total_amount_minor' => 'integer', 'total_fee_minor' => 'integer', 'expected_at' => 'datetime', 'settled_at' => 'datetime', 'metadata' => 'array'];
    public function provider(): BelongsTo { return $this->belongsTo(PaymentProvider::class); }
    public function items(): HasMany { return $this->hasMany(SettlementItem::class); }
}
