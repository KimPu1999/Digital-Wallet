<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class QaReleaseGate extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['gate_key','status','required','evidence_reference','evaluated_by','evaluated_at','metadata']; protected $casts=['required'=>'boolean','evaluated_at'=>'datetime','metadata'=>'array']; }
