<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class FinancialPartner extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'financial_partners';
    protected $guarded = [];
    protected $casts = ['capabilities' => 'array', 'sandbox_only' => 'boolean'];
}
