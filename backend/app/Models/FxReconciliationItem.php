<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class FxReconciliationItem extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['conversion_id','item_type','status','expected_minor','actual_minor','difference_minor','metadata']; protected $casts=['metadata'=>'array']; }
