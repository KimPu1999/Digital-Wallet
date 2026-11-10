<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MerchantAccessService
{
    public function assert(User $user, Merchant $merchant, array $roles = ['owner', 'manager', 'finance', 'developer', 'analyst']): void
    {
        $membership = DB::table('merchant_memberships')->where('merchant_id', $merchant->id)->where('user_id', $user->id)->first();
        if (!$membership || !in_array($membership->role, $roles, true)) throw ValidationException::withMessages(['merchant' => 'Merchant access denied.']);
        if ($merchant->status !== 'active') throw ValidationException::withMessages(['merchant' => 'Merchant is not eligible for sandbox payment operations.']);
        $verification = DB::table('merchant_verifications')->where('merchant_id', $merchant->id)->latest('created_at')->first();
        if ($verification && $verification->status !== 'approved') throw ValidationException::withMessages(['kyb' => 'Approved merchant verification is required.']);
    }

    public function role(User $user, Merchant $merchant): ?string
    {
        return DB::table('merchant_memberships')->where('merchant_id', $merchant->id)->where('user_id', $user->id)->value('role');
    }
}
