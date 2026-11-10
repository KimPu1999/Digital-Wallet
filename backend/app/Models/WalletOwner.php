<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany;
class WalletOwner extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['user_id','merchant_id','owner_type','status']; public function user(): BelongsTo { return $this->belongsTo(User::class); } public function merchant(): BelongsTo { return $this->belongsTo(Merchant::class); } public function wallets(): HasMany { return $this->hasMany(Wallet::class,'owner_id'); } }
