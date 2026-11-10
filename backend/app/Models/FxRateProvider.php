<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class FxRateProvider extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['code','name','provider_type','status','timeout_ms','stale_after_seconds','metadata']; protected $casts=['metadata'=>'array']; }
