<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MobileReleaseCandidate extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['version_name', 'version_code', 'channel', 'status', 'android_build_id', 'ios_build_id', 'approval_required', 'approved_by', 'approved_at', 'metadata'];
    protected $casts = ['version_code' => 'integer', 'approval_required' => 'boolean', 'approved_at' => 'datetime', 'metadata' => 'array'];
}
