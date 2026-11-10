<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MobileCrashReport extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['platform', 'release_version', 'fingerprint', 'severity', 'occurrence_count', 'status', 'metadata'];
    protected $casts = ['occurrence_count' => 'integer', 'metadata' => 'array'];
}
