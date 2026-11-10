<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Merchant extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['legal_name','display_name','status']; }
