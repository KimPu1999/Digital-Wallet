<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class TransferStepUpChallenge extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['user_id','transfer_id','challenge_hash','method','status','expires_at','consumed_at','session_fingerprint']; protected $casts=['expires_at'=>'datetime','consumed_at'=>'datetime']; }
