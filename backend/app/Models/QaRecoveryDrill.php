<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class QaRecoveryDrill extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['environment','backup_reference','restore_started_at','restore_finished_at','rpo_seconds','rto_seconds','schema_validated','ledger_reconciled','idempotency_validated','status','evidence']; protected $casts=['restore_started_at'=>'datetime','restore_finished_at'=>'datetime','rpo_seconds'=>'integer','rto_seconds'=>'integer','schema_validated'=>'boolean','ledger_reconciled'=>'boolean','idempotency_validated'=>'boolean','evidence'=>'array']; }
