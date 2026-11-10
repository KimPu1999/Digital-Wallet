<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasOne;
class Country extends Model { protected $primaryKey='code'; public $incrementing=false; protected $keyType='string'; protected $fillable=['code','name','is_active']; public function capabilities(): HasOne { return $this->hasOne(CountryCapability::class,'country_code','code'); } }
