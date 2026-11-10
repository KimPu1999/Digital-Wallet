<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AdminStepUpService
{
    public function issue(User $user): array
    {
        $code = (string) random_int(100000, 999999);
        $id = (string) Str::uuid();
        DB::table('mfa_challenges')->insert(['id' => $id, 'user_id' => $user->id, 'purpose' => 'admin_step_up', 'challenge_hash' => hash('sha256', $code), 'expires_at' => now()->addMinutes(5), 'created_at' => now(), 'updated_at' => now()]);
        return ['challenge_id' => $id, 'expires_at' => now()->addMinutes(5), 'sandbox_code' => $code, 'sandbox_only' => true];
    }

    public function consume(User $user, string $challengeId, string $code): void
    {
        $challenge = DB::table('mfa_challenges')->where('id', $challengeId)->where('user_id', $user->id)->where('purpose', 'admin_step_up')->lockForUpdate()->first();
        if (!$challenge || $challenge->used_at || now()->greaterThan($challenge->expires_at) || !hash_equals($challenge->challenge_hash, hash('sha256', $code))) throw ValidationException::withMessages(['step_up' => 'A valid recent admin step-up challenge is required.']);
        DB::table('mfa_challenges')->where('id', $challenge->id)->update(['used_at' => now(), 'updated_at' => now()]);
        session(['platform_admin_step_up_at' => now()->toIso8601String()]);
    }
}
