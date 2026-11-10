<?php

namespace Tests\Feature;

use App\Models\MfaMethod;
use App\Models\PlatformAdminMembership;
use App\Models\PlatformRole;
use App\Models\User;
use App\Services\AdminApprovalService;
use App\Services\AdminStepUpService;
use App\Services\PlatformAdminAccessService;
use Database\Seeders\PlatformAdminSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class PlatformAdminTest extends TestCase
{
    private User $superAdmin;
    private User $complianceAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
        (new PlatformAdminSeeder())->run();
        $this->superAdmin = $this->makeAdmin('super@example.com', 'platform_super_admin');
        $this->complianceAdmin = $this->makeAdmin('compliance@example.com', 'compliance_admin');
    }

    public function test_platform_permissions_and_mfa_are_server_enforced(): void
    {
        $this->assertNotNull(app(PlatformAdminAccessService::class)->assert($this->superAdmin, 'settings.manage'));
        $this->expectException(\Illuminate\Validation\UnauthorizedException::class);
        app(PlatformAdminAccessService::class)->assert($this->complianceAdmin, 'settings.manage');
    }

    public function test_protected_overview_rejects_non_admin_and_accepts_verified_admin(): void
    {
        $this->getJson('/api/v1/admin/platform/overview')->assertUnauthorized();
        $this->withSession(['platform_admin_authenticated_at' => now()->toIso8601String()])->actingAs($this->complianceAdmin)->getJson('/api/v1/admin/platform/overview')->assertOk()->assertJsonPath('data.sandbox_only', true);
    }

    public function test_maker_cannot_self_approve_and_second_admin_can_complete_dual_approval(): void
    {
        $request = app(AdminApprovalService::class)->request($this->superAdmin, 'approvals.request', 'settings.change', 'platform_settings', null, 'Sandbox settings review', 'approval-1', 2);
        $stepUp = app(AdminStepUpService::class)->issue($this->superAdmin);
        $this->expectException(ValidationException::class);
        app(AdminApprovalService::class)->decide($this->superAdmin, 'approvals.decide', $request, 'approved', 'Self approval should fail', $stepUp['challenge_id'], $stepUp['sandbox_code']);
    }

    public function test_feature_flags_default_to_disabled_and_admin_step_up_is_one_time(): void
    {
        $this->assertSame(false, (bool) DB::table('platform_feature_flags')->where('flag_key', 'customer_money_movement_enabled')->value('enabled'));
        $challenge = app(AdminStepUpService::class)->issue($this->superAdmin);
        app(AdminStepUpService::class)->consume($this->superAdmin, $challenge['challenge_id'], $challenge['sandbox_code']);
        $this->expectException(ValidationException::class);
        app(AdminStepUpService::class)->consume($this->superAdmin, $challenge['challenge_id'], $challenge['sandbox_code']);
    }

    public function test_dual_approval_requires_two_distinct_approvers(): void
    {
        $second = $this->makeAdmin('second-super@example.com', 'platform_super_admin');
        $third = $this->makeAdmin('third-super@example.com', 'platform_super_admin');
        $request = app(AdminApprovalService::class)->request($this->superAdmin, 'approvals.request', 'feature.change', 'platform_feature_flags', null, 'Dual review', 'approval-dual', 2);
        $secondChallenge = app(AdminStepUpService::class)->issue($second);
        $pending = app(AdminApprovalService::class)->decide($second, 'approvals.decide', $request, 'approved', 'First approval', $secondChallenge['challenge_id'], $secondChallenge['sandbox_code']);
        $this->assertSame('pending', $pending->status);
        $thirdChallenge = app(AdminStepUpService::class)->issue($third);
        $approved = app(AdminApprovalService::class)->decide($third, 'approvals.decide', $request, 'approved', 'Second approval', $thirdChallenge['challenge_id'], $thirdChallenge['sandbox_code']);
        $this->assertSame('approved', $approved->status);
    }

    private function makeAdmin(string $email, string $roleName): User
    {
        $user = User::create(['name' => $roleName, 'email' => $email, 'password' => 'password-password', 'account_status' => 'active', 'user_type' => 'staff', 'email_verified_at' => now()]);
        $user->forceFill(['is_active' => true, 'email_verified_at' => now()])->save();
        MfaMethod::create(['user_id' => $user->id, 'type' => 'totp', 'secret_encrypted' => 'test', 'is_primary' => true, 'verified_at' => now()]);
        $role = PlatformRole::where('name', $roleName)->firstOrFail();
        PlatformAdminMembership::create(['user_id' => $user->id, 'platform_role_id' => $role->id, 'status' => 'active']);
        return $user;
    }
}
