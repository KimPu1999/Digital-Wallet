<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SupportTicketEvent extends Model { use HasUuids; public $incrementing=false; public $timestamps=false; protected $keyType='string'; protected $fillable=['support_ticket_id','actor_id','event_type','from_status','to_status','notes','metadata','created_at']; protected $casts=['metadata'=>'array','created_at'=>'datetime']; public function ticket(): BelongsTo { return $this->belongsTo(SupportTicket::class,'support_ticket_id'); } }
