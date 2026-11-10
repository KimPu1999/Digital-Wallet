<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CustomerRiskProfile extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['user_id','classification','assessed_at','review_due_at','screening_status','monitoring_flags','assessment_references','last_assessed_by']; protected $casts=['assessed_at'=>'datetime','review_due_at'=>'datetime','monitoring_flags'=>'array','assessment_references'=>'array']; public function user(): BelongsTo { return $this->belongsTo(User::class); } }
