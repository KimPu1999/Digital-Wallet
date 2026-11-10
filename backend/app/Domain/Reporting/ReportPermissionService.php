<?php
namespace App\Domain\Reporting;
use App\Models\Merchant;
use App\Models\User;
use App\Services\MerchantAccessService;
use App\Services\PlatformAdminAccessService;
final class ReportPermissionService {
    public function __construct(private PlatformAdminAccessService $platform, private MerchantAccessService $merchant) {}
    public function platform(User $user, string $permission = 'financial.read'): void { $this->platform->assert($user, $permission); }
    public function merchant(User $user, Merchant $merchant): void { $this->merchant->assert($user, $merchant, ['owner', 'manager', 'finance', 'analyst']); }
}
