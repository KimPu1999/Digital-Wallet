<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Notification extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['type','notifiable_type','notifiable_id','data','read_at']; protected $casts=['data'=>'array','read_at'=>'datetime']; public function user(): BelongsTo { return $this->belongsTo(User::class,'notifiable_id'); } }
