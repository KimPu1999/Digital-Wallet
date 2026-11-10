<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TransferReceipt extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['transfer_id','receipt_reference','receipt_hash','snapshot','issued_at']; protected $casts=['snapshot'=>'array','issued_at'=>'datetime']; public function transfer(): BelongsTo { return $this->belongsTo(Transfer::class); } }
