<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LoginActivity extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['user_id','event','ip_address','user_agent','occurred_at']; protected $casts=['occurred_at'=>'datetime']; public function user(): BelongsTo { return $this->belongsTo(User::class); } }
