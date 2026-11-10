<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class WalletStatusHistory extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $table='wallet_status_history'; protected $fillable=['wallet_id','actor_id','from_status','to_status','reason']; public function wallet(): BelongsTo { return $this->belongsTo(Wallet::class); } public function actor(): BelongsTo { return $this->belongsTo(User::class,'actor_id'); } }
