<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class TransferLimitUsage extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['user_id','limit_id','currency_code','usage_date','daily_minor','monthly_minor','version']; protected $casts=['usage_date'=>'date']; }
