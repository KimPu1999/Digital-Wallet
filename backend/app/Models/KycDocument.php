<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class KycDocument extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['kyc_case_id','document_type','storage_key','mime_type','size_bytes','scan_status','review_status','uploaded_at']; protected $casts=['uploaded_at'=>'datetime']; public function kycCase(): BelongsTo { return $this->belongsTo(KycCase::class); } }
