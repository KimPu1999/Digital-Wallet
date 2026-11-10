<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class ReportValuation extends Model { use HasUuids; public $incrementing=false; public $timestamps=false; protected $keyType='string'; protected $fillable=['reporting_currency_code','source_currency_code','rate','rate_source','rate_timestamp','rate_version','valuation_date','rounding_policy','created_at']; protected $casts=['rate'=>'decimal:18','rate_timestamp'=>'datetime','valuation_date'=>'date','created_at'=>'datetime']; }
