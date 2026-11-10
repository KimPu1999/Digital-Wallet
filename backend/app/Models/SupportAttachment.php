<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SupportAttachment extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['support_ticket_id','support_message_id','uploaded_by','storage_key','original_name','mime_type','size_bytes','scan_status','checksum']; protected $hidden=['storage_key','checksum']; public function ticket(): BelongsTo { return $this->belongsTo(SupportTicket::class,'support_ticket_id'); } }
