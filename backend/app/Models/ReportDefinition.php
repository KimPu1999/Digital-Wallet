<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class ReportDefinition extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['code','name','report_type','version','scope','definition','status','created_by']; protected $casts=['definition'=>'array','version'=>'integer']; }
