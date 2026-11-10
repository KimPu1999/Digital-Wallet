<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OperationalIncident extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['incident_reference', 'category', 'severity', 'status', 'title', 'summary', 'owner_id', 'detected_at', 'resolved_at', 'metadata'];
    protected $casts = ['detected_at' => 'datetime', 'resolved_at' => 'datetime', 'metadata' => 'array'];
}
