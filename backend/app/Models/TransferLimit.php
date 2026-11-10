<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class TransferLimit extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['scope_type','scope_key','currency_code','country_code','kyc_tier','per_transaction_minor','daily_minor','monthly_minor','is_enabled','effective_from','effective_until','metadata']; protected $casts=['is_enabled'=>'boolean','effective_from'=>'date','effective_until'=>'date','metadata'=>'array']; }
