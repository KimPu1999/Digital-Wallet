<?php

namespace App\Services;

use App\Models\PlatformAdminMembership;
use App\Models\User;
use Illuminate\Validation\UnauthorizedException;

final class PlatformAdminAccessService
{
    public function __construct(private MfaService $mfa, private AdminStepUpService $stepUp) {}

    public function memberships(User $user)
    {
        return PlatformAdminMembership::with('role.permissions')->where('user_id', $user->id)->where('status', 'active')->get();
    }

    public function assert(User $user, string $permission): PlatformAdminMembership
    {
        $memberships = $this->memberships($user);
        if ($memberships->isEmpty() || !$user->is_active || !$this->mfa->enabled($user)) throw new UnauthorizedException('Platform administrator access requires an active account and verified MFA.');
        $membership = $memberships->first(fn ($item) => $item->role->name === 'platform_super_admin' || $item->role->permissions->contains('name', $permission));
        if (!$membership) throw new UnauthorizedException('Platform permission denied.');
        return $membership;
    }

    public function assertSensitive(User $user, string $permission, string $challengeId, string $challengeCode): PlatformAdminMembership
    {
        $membership = $this->assert($user, $permission);
        $this->stepUp->consume($user, $challengeId, $challengeCode);
        $membership->update(['last_step_up_at' => now()]);
        return $membership;
    }
}
