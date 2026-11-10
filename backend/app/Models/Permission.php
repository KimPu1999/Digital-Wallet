<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Permission extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['name','guard_name']; }
