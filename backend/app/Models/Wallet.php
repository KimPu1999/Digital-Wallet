<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Wallet extends Model { use \Illuminate\Database\Eloquent\Concerns\HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['user_id','currency_id','label','status','wallet_type']; public function user(): BelongsTo { return $this->belongsTo(User::class); } public function currency(): BelongsTo { return $this->belongsTo(Currency::class); } }
