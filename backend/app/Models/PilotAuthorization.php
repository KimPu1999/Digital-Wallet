<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class PilotAuthorization extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'pilot_authorizations';
    protected $guarded = [];
    protected $casts = [
        'approved_customer_group' => 'array',
        'approved_merchant_group' => 'array',
        'allowed_currencies' => 'array',
        'allowed_capabilities' => 'array',
        'risk_limits' => 'array',
        'provider_dependencies' => 'array',
    ];
}
