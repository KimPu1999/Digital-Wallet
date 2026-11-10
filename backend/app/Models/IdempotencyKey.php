<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['scope', 'key', 'request_fingerprint', 'status', 'response_code', 'response_body', 'expires_at'];
    protected $casts = ['response_body' => 'array', 'expires_at' => 'datetime'];
}
