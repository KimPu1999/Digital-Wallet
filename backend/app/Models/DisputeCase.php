<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasMany;
class DisputeCase extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['user_id','merchant_id','support_ticket_id','payment_intent_id','opened_by','assigned_to','dispute_reference','classification','status','priority','reason','resolution_summary']; public function events(): HasMany { return $this->hasMany(DisputeEvent::class); } }
