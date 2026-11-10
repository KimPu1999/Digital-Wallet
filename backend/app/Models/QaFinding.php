<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class QaFinding extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['qa_test_run_id','severity','status','area','title','evidence','remediation']; protected $casts=['evidence'=>'array']; }
