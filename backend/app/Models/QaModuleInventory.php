<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class QaModuleInventory extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'qa_module_inventory';
    protected $guarded = [];
    protected $casts = ['phase' => 'integer', 'audited_at' => 'datetime', 'metadata' => 'array'];
}
