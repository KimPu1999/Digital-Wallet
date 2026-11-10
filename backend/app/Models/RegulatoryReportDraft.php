<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class RegulatoryReportDraft extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['report_type','jurisdiction','status','created_by','approved_by','summary','evidence_references','report_payload']; protected $casts=['evidence_references'=>'array','report_payload'=>'array']; protected $hidden=['report_payload']; }
