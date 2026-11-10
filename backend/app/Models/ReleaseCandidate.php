<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ReleaseCandidate extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['release_reference', 'product', 'version', 'environment', 'status', 'commits', 'test_evidence', 'rollback_plan', 'created_by'];
    protected $casts = ['commits' => 'array', 'test_evidence' => 'array'];
}
