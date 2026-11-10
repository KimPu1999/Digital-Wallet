<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class WalletCurrencyAccount extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['wallet_id','currency_id','status']; public function wallet(): BelongsTo { return $this->belongsTo(Wallet::class); } public function currency(): BelongsTo { return $this->belongsTo(Currency::class); } }
