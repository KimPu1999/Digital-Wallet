<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationItem extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['reconciliation_run_id', 'item_type', 'entity_type', 'entity_id', 'status', 'expected_minor', 'actual_minor', 'difference_minor', 'metadata'];
    protected $casts = ['expected_minor' => 'integer', 'actual_minor' => 'integer', 'difference_minor' => 'integer', 'metadata' => 'array'];
    public function run(): BelongsTo { return $this->belongsTo(ReconciliationRun::class, 'reconciliation_run_id'); }
}
