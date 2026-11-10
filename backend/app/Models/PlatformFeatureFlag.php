<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class PlatformFeatureFlag extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['flag_key','enabled','environment','metadata']; protected $casts=['enabled'=>'boolean','metadata'=>'array']; }
