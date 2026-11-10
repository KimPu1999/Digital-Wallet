<?php

namespace App\Policies;

use App\Models\PaymentIntent;
use App\Models\User;

final class PaymentPolicy
{
    public function view(User $user, PaymentIntent $intent): bool
    {
        if ((string) $intent->user_id === (string) $user->id) return true;
        if ($intent->merchant_id === null) return false;
        return \Illuminate\Support\Facades\DB::table('merchant_memberships')->where('merchant_id', $intent->merchant_id)->where('user_id', $user->id)->exists();
    }
}
