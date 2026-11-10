<?php

namespace Tests\Feature;

use App\Models\MfaMethod;
use App\Models\PlatformAdminMembership;
use App\Models\User;
use Database\Seeders\PlatformAdminSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

final class CreateSuperAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
        Notification::fake();
    }

    public function test_interactive_command_creates_staff_membership_without_printing_password(): void
    {
        $password = 'Secure!26';

        $result = $this->runCommand(['First Platform Admin', 'first-admin@example.com', $password, $password, 'yes']);

        $this->assertSame(0, $result->getStatusCode());
        $this->assertStringNotContainsString($password, $result->getDisplay());

        $user = User::where('email', 'first-admin@example.com')->firstOrFail();
        $this->assertSame('staff', $user->user_type);
        $this->assertSame('pending_email', $user->account_status);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertDatabaseHas('platform_admin_memberships', ['user_id' => $user->id, 'status' => 'active']);
        $this->assertDatabaseHas('role_assignments', ['user_id' => $user->id]);
        $this->assertSame(1, PlatformAdminMembership::where('user_id', $user->id)->count());
    }

    public function test_existing_email_is_rejected_without_overwriting_the_account(): void
    {
        $existing = User::create([
            'name' => 'Existing Account',
            'email' => 'existing@example.com',
            'password' => 'ExistingPassword!2026',
            'account_status' => 'active',
            'user_type' => 'customer',
        ]);

        $result = $this->runCommand(['Replacement Name', 'existing@example.com']);

        $this->assertSame(1, $result->getStatusCode());
        $this->assertSame('Existing Account', $existing->refresh()->name);
        $this->assertSame('customer', $existing->refresh()->user_type);
        $this->assertDatabaseMissing('platform_admin_memberships', ['user_id' => $existing->id]);
    }

    public function test_bootstrap_refuses_to_create_a_second_platform_super_admin(): void
    {
        (new PlatformAdminSeeder())->run();
        $existing = User::create([
            'name' => 'Existing Super Admin',
            'email' => 'existing-super@example.com',
            'password' => 'ExistingPassword!2026',
            'account_status' => 'active',
            'user_type' => 'staff',
            'email_verified_at' => now(),
        ]);
        $role = DB::table('platform_roles')->where('name', 'platform_super_admin')->value('id');
        PlatformAdminMembership::create(['user_id' => $existing->id, 'platform_role_id' => $role, 'status' => 'active']);

        $result = $this->runCommand(['Second Admin', 'second-admin@example.com', 'Secure!26', 'Secure!26', 'yes']);

        $this->assertSame(1, $result->getStatusCode());
        $this->assertDatabaseMissing('users', ['email' => 'second-admin@example.com']);
    }

    public function test_controlled_invitation_creates_an_additional_super_admin_without_printing_password(): void
    {
        $password = 'Invite!26';

        $result = $this->runInviteCommand([
            'Additional Admin',
            'additional-admin@example.com',
            $password,
            $password,
            'yes',
        ]);

        $this->assertSame(0, $result->getStatusCode());
        $this->assertStringNotContainsString($password, $result->getDisplay());

        $user = User::where('email', 'additional-admin@example.com')->firstOrFail();
        $this->assertSame('staff', $user->user_type);
        $this->assertSame('pending_email', $user->account_status);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertDatabaseHas('platform_admin_memberships', [
            'user_id' => $user->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('platform_roles', ['name' => 'platform_super_admin']);
    }

    public function test_admin_login_requires_platform_membership_email_verification_and_mfa(): void
    {
        (new PlatformAdminSeeder())->run();
        $admin = User::create([
            'name' => 'Verified Super Admin',
            'email' => 'verified-admin@example.com',
            'password' => 'VerifiedPassword!2026',
            'account_status' => 'active',
            'user_type' => 'staff',
            'email_verified_at' => now(),
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $role = DB::table('platform_roles')->where('name', 'platform_super_admin')->value('id');
        PlatformAdminMembership::create(['user_id' => $admin->id, 'platform_role_id' => $role, 'status' => 'active']);

        $this->postJson('/api/v1/auth/admin/login', ['email' => $admin->email, 'password' => 'VerifiedPassword!2026'])
            ->assertStatus(403)
            ->assertJsonPath('mfa_required', true);

        MfaMethod::create(['user_id' => $admin->id, 'type' => 'totp', 'secret_encrypted' => Crypt::encryptString('JBSWY3DPEHPK3PXP'), 'is_primary' => true, 'verified_at' => now()]);

        $this->postJson('/api/v1/auth/admin/login', ['email' => $admin->email, 'password' => 'VerifiedPassword!2026'])
            ->assertStatus(403)
            ->assertJsonPath('challenge_required', true);

        $this->postJson('/api/v1/auth/admin/mfa/verify', ['code' => $this->totp('JBSWY3DPEHPK3PXP')])
            ->assertOk()
            ->assertJsonPath('data.mfa_required', true);

        $this->getJson('/api/v1/admin/platform/overview')->assertOk();
    }

    public function test_customer_cannot_use_admin_login_or_platform_endpoints(): void
    {
        $customer = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'password' => 'CustomerPassword!2026',
            'account_status' => 'active',
            'user_type' => 'customer',
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/admin/login', ['email' => $customer->email, 'password' => 'CustomerPassword!2026'])
            ->assertStatus(422);
        $this->actingAs($customer)->getJson('/api/v1/admin/platform/overview')->assertForbidden();
    }

    public function test_unverified_admin_is_blocked_and_resend_response_is_generic(): void
    {
        (new PlatformAdminSeeder())->run();
        $admin = User::create(['name' => 'Pending Admin', 'email' => 'pending-admin@example.com', 'password' => 'PendingPassword!2026', 'account_status' => 'pending_email', 'user_type' => 'staff']);
        $role = DB::table('platform_roles')->where('name', 'platform_super_admin')->value('id');
        PlatformAdminMembership::create(['user_id' => $admin->id, 'platform_role_id' => $role, 'status' => 'active']);

        $this->postJson('/api/v1/auth/admin/login', ['email' => $admin->email, 'password' => 'PendingPassword!2026'])
            ->assertForbidden()->assertJsonPath('email_verification_required', true);
        $this->postJson('/api/v1/auth/admin/email/resend', ['email' => $admin->email])
            ->assertOk()->assertJsonPath('message', 'If an eligible administrator account matches, verification instructions will be sent.');
    }

    public function test_inactive_membership_is_denied(): void
    {
        (new PlatformAdminSeeder())->run();
        $admin = User::create(['name' => 'Inactive Admin', 'email' => 'inactive-admin@example.com', 'password' => 'InactivePassword!2026', 'account_status' => 'active', 'user_type' => 'staff', 'email_verified_at' => now()]);
        $role = DB::table('platform_roles')->where('name', 'platform_super_admin')->value('id');
        PlatformAdminMembership::create(['user_id' => $admin->id, 'platform_role_id' => $role, 'status' => 'suspended']);

        $this->postJson('/api/v1/auth/admin/login', ['email' => $admin->email, 'password' => 'InactivePassword!2026'])->assertUnprocessable();
    }

    public function test_invalid_and_expired_admin_mfa_challenges_are_rejected(): void
    {
        (new PlatformAdminSeeder())->run();
        $admin = User::create(['name' => 'MFA Admin', 'email' => 'mfa-admin@example.com', 'password' => 'MfaPassword!2026', 'account_status' => 'active', 'user_type' => 'staff', 'email_verified_at' => now()]);
        $role = DB::table('platform_roles')->where('name', 'platform_super_admin')->value('id');
        PlatformAdminMembership::create(['user_id' => $admin->id, 'platform_role_id' => $role, 'status' => 'active']);
        MfaMethod::create(['user_id' => $admin->id, 'type' => 'totp', 'secret_encrypted' => Crypt::encryptString('JBSWY3DPEHPK3PXP'), 'is_primary' => true, 'verified_at' => now()]);

        $this->postJson('/api/v1/auth/admin/login', ['email' => $admin->email, 'password' => 'MfaPassword!2026'])->assertForbidden()->assertJsonPath('challenge_required', true);
        $this->postJson('/api/v1/auth/admin/mfa/verify', ['code' => '000000'])->assertUnprocessable();
        $this->withSession(['platform_admin_mfa_pending_user_id' => $admin->id, 'platform_admin_mfa_pending_until' => now()->subMinute()->toIso8601String()])->postJson('/api/v1/auth/admin/mfa/verify', ['code' => '000000'])->assertUnprocessable();
    }

    private function runCommand(array $answers): CommandTester
    {
        $command = $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->all()['platform:create-super-admin'];
        $tester = new CommandTester($command);
        $tester->setInputs($answers);
        $tester->execute([], ['interactive' => true]);
        return $tester;
    }

    private function runInviteCommand(array $answers): CommandTester
    {
        $command = $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->all()['platform:invite-admin'];
        $tester = new CommandTester($command);
        $tester->setInputs($answers);
        $tester->execute(['--role' => 'platform_super_admin'], ['interactive' => true]);
        return $tester;
    }

    private function totp(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($secret) as $character) $bits .= str_pad(decbin(strpos($alphabet, $character)), 5, '0', STR_PAD_LEFT);
        $key = '';
        foreach (str_split($bits, 8) as $chunk) if (strlen($chunk) === 8) $key .= chr(bindec($chunk));
        $counter = intdiv(now()->timestamp, 30);
        $hash = hash_hmac('sha1', pack('N*', 0, $counter), $key, true);
        $offset = ord($hash[19]) & 0xf;
        $value = ((ord($hash[$offset]) & 0x7f) << 24) | ((ord($hash[$offset + 1]) & 0xff) << 16) | ((ord($hash[$offset + 2]) & 0xff) << 8) | (ord($hash[$offset + 3]) & 0xff);
        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }
}
