<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CountryCapability extends Model { protected $fillable=['country_code','registration_enabled','kyc_enabled','wallet_enabled','deposits_enabled','withdrawals_enabled','transfers_enabled','fx_enabled']; protected $casts=['registration_enabled'=>'boolean','kyc_enabled'=>'boolean','wallet_enabled'=>'boolean','deposits_enabled'=>'boolean','withdrawals_enabled'=>'boolean','transfers_enabled'=>'boolean','fx_enabled'=>'boolean']; public function country(): BelongsTo { return $this->belongsTo(Country::class,'country_code','code'); } }
