<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReconciliationRun extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['run_type', 'status', 'started_by', 'summary', 'started_at', 'completed_at'];
    protected $casts = ['summary' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    public function items(): HasMany { return $this->hasMany(ReconciliationItem::class); }
}
