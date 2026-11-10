<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class BeneficiaryAccount extends Model { use HasUuids; public $incrementing=false; protected $keyType='string'; protected $fillable=['beneficiary_id','account_type','currency_code','account_details_encrypted','account_last4','status']; protected $hidden=['account_details_encrypted']; public function beneficiary(): BelongsTo { return $this->belongsTo(Beneficiary::class); } }
