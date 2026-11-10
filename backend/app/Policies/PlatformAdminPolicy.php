<?php

namespace App\Policies;

use App\Models\User;
use App\Services\PlatformAdminAccessService;

final class PlatformAdminPolicy
{
    public function view(User $user, string $permission): bool
    {
        try { app(PlatformAdminAccessService::class)->assert($user, $permission); return true; } catch (\Throwable) { return false; }
    }
}
