<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantSettlement extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['merchant_id', 'settlement_batch_id', 'settlement_reference', 'currency_code', 'gross_amount_minor', 'fee_amount_minor', 'refund_amount_minor', 'net_amount_minor', 'status', 'period_start', 'period_end', 'settled_at', 'metadata'];
    protected $casts = ['gross_amount_minor' => 'integer', 'fee_amount_minor' => 'integer', 'refund_amount_minor' => 'integer', 'net_amount_minor' => 'integer', 'period_start' => 'datetime', 'period_end' => 'datetime', 'settled_at' => 'datetime', 'metadata' => 'array'];
    public function merchant(): BelongsTo { return $this->belongsTo(Merchant::class); }
    public function settlementBatch(): BelongsTo { return $this->belongsTo(SettlementBatch::class); }
}
