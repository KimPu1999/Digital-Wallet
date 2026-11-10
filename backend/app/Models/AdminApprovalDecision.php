<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AdminApprovalDecision extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['approval_request_id','decided_by','decision','reason']; public function request(): BelongsTo { return $this->belongsTo(AdminApprovalRequest::class,'approval_request_id'); } public function decider(): BelongsTo { return $this->belongsTo(User::class,'decided_by'); } }
