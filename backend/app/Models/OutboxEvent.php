<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OutboxEvent extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['event_type', 'aggregate_type', 'aggregate_id', 'payload', 'status', 'attempts', 'published_at', 'available_at'];
    protected $casts = ['payload' => 'array', 'published_at' => 'datetime', 'available_at' => 'datetime'];
}
