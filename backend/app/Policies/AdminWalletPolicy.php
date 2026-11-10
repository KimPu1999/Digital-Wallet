<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;

final class AdminWalletPolicy
{
    private function isWalletStaff(User $user): bool
    {
        return collect(['platform_super_admin', 'compliance_admin', 'finance_operations', 'risk_analyst', 'support_agent', 'read_only_auditor'])->contains(fn ($role) => $user->hasRole($role));
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
