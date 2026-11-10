<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SupportMessage extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['support_ticket_id','sender_id','sender_type','visibility','body','client_message_key']; public function ticket(): BelongsTo { return $this->belongsTo(SupportTicket::class,'support_ticket_id'); } }
