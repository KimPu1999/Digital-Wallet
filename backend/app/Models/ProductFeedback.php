<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProductFeedback extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['source', 'category', 'priority', 'status', 'redacted_summary', 'product_area', 'owner_id', 'resolution_notes'];
}
