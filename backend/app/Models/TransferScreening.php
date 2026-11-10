<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TransferScreening extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['transfer_id','screening_type','provider','status','decision','reference','reason','metadata']; protected $casts=['metadata'=>'array']; public function transfer(): BelongsTo { return $this->belongsTo(Transfer::class); } }
