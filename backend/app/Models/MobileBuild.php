<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MobileBuild extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['platform', 'channel', 'version_name', 'version_code', 'commit_sha', 'status', 'artifact_uri', 'started_at', 'finished_at', 'metadata'];
    protected $casts = ['version_code' => 'integer', 'started_at' => 'datetime', 'finished_at' => 'datetime', 'metadata' => 'array'];
}
