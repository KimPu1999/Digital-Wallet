<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FxConversionLeg extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['conversion_id','ledger_account_id','currency_code','leg_type','side','amount_minor','sequence']; public function conversion(): BelongsTo { return $this->belongsTo(FxConversion::class,'conversion_id'); } public function ledgerAccount(): BelongsTo { return $this->belongsTo(LedgerAccount::class); } }
