<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class PilotProgram extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'pilot_programs';
    protected $guarded = [];
    protected $casts = [
        'jurisdictions' => 'array',
        'supported_currencies' => 'array',
        'allowed_features' => 'array',
        'provider_capabilities' => 'array',
        'transaction_types' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'metadata' => 'array',
        'sandbox_only' => 'boolean',
    ];
}
