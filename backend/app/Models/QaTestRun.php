<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class QaTestRun extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['suite','category','status','environment','commit_sha','started_at','finished_at','duration_ms','summary','artifact_uri']; protected $casts=['started_at'=>'datetime','finished_at'=>'datetime','duration_ms'=>'integer','summary'=>'array']; }
