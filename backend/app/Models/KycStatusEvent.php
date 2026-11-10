<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class KycStatusEvent extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['kyc_case_id', 'actor_id', 'from_status', 'to_status', 'reason', 'metadata'];
    protected $casts = ['metadata' => 'array'];

    public function case(): BelongsTo
    {
        return $this->belongsTo(KycCase::class, 'kyc_case_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
