<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ScreeningMatch extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['screening_request_id','status','list_source','match_reference','match_score','subject_facts','review_metadata','reviewed_by','reviewed_at']; protected $casts=['match_score'=>'float','subject_facts'=>'array','review_metadata'=>'array','reviewed_at'=>'datetime']; public function request(): BelongsTo { return $this->belongsTo(ScreeningRequest::class,'screening_request_id'); } }
