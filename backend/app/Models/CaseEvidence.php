<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CaseEvidence extends Model { use HasUuids; public $incrementing=false; public $timestamps=false; protected $keyType='string'; protected $fillable=['compliance_case_id','uploaded_by','evidence_type','storage_reference','checksum','access_class','metadata','created_at']; protected $casts=['metadata'=>'array','created_at'=>'datetime']; protected $hidden=['storage_reference','checksum']; public function complianceCase(): BelongsTo { return $this->belongsTo(ComplianceCase::class); } }
