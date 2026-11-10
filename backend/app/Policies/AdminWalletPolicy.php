<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;

final class AdminWalletPolicy
{
    private function isWalletStaff(User $user): bool
    {
        return $user->hasRole('platform_super_admin') || $user->hasRole('compliance_admin') || $user->hasRole('support_agent');
    }

    public function viewAny(User $user): bool
    {
        return $this->isWalletStaff($user);
    }

    public function view(User $user, Wallet $wallet): bool
    {
        return $this->isWalletStaff($user);
    }
}
