<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PrivacyRequest extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['subject_user_id', 'request_type', 'status', 'verification_status', 'assigned_to', 'scope_summary', 'resolution_summary', 'completed_at'];
    protected $casts = ['completed_at' => 'datetime'];
}
