<?php

namespace Tests\Feature;

use App\Domain\AML\RiskRuleEngine;
use App\Domain\Compliance\ComplianceCaseService;
use App\Domain\Compliance\SanctionsScreeningService;
use App\Models\PlatformAdminMembership;
use App\Models\PlatformRole;
use App\Models\User;
use Database\Seeders\PlatformAdminSeeder;
use Database\Seeders\SecurityComplianceSeeder;
use App\Services\AdminStepUpService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SecurityComplianceTest extends TestCase
{
    private User $admin;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
        (new PlatformAdminSeeder())->run();
        (new SecurityComplianceSeeder())->run();
        $this->admin = $this->makeAdmin();
        $this->customer = User::create(['name' => 'Customer', 'email' => 'customer-security@example.com', 'password' => 'password-password', 'account_status' => 'active', 'user_type' => 'customer', 'email_verified_at' => now()]);
        $this->customer->forceFill(['is_active' => true])->save();
    }

    public function test_rule_evaluation_is_versioned_explainable_and_idempotent(): void
    {
        $first = app(RiskRuleEngine::class)->evaluate('user', $this->customer->id, 'failed_auth_velocity', ['failed_attempts' => 8]);
        $second = app(RiskRuleEngine::class)->evaluate('user', $this->customer->id, 'failed_auth_velocity', ['failed_attempts' => 8]);
        $this->assertSame('review', $first->decision);
        $this->assertNotNull($first->risk_rule_version_id);
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseHas('risk_alerts', ['risk_evaluation_id' => $first->id]);
    }

    public function test_failed_screening_is_not_clear_and_potential_match_requires_review(): void
    {
        $failed = app(SanctionsScreeningService::class)->screen('user', $this->customer->id, ['name' => 'Sandbox', 'mock_mode' => 'timeout']);
        $potential = app(SanctionsScreeningService::class)->screen('user', $this->customer->id, ['name' => 'Sandbox', 'mock_mode' => 'potential_match']);
        $this->assertSame('failed', $failed->status);
        $this->assertSame('potential_match', $potential->status);
        $this->assertCount(1, $potential->matches);
        $this->assertSame('potential_match', $potential->matches->first()->status);
        $evaluation = app(RiskRuleEngine::class)->evaluate('user', $this->customer->id, 'payment_activity', ['screening_required' => true, 'screening_status' => 'failed']);
        $this->assertSame('review', $evaluation->decision);
    }

    public function test_platform_case_access_and_valid_transition_are_authorized(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/platform/compliance/cases', ['subject_type' => 'user', 'subject_id' => $this->customer->id, 'priority' => 'high', 'case_type' => 'aml', 'reason' => 'Sandbox analyst review']);
        $response->assertCreated()->assertJsonPath('data.status', 'open');
        $caseId = $response->json('data.id');
        $challenge = app(AdminStepUpService::class)->issue($this->admin);
        $this->actingAs($this->admin)->postJson('/api/v1/platform/compliance/cases/'.$caseId.'/events', ['status' => 'assigned', 'notes' => 'Assigned to sandbox analyst', 'challenge_id' => $challenge['challenge_id'], 'challenge_code' => $challenge['sandbox_code']])->assertOk()->assertJsonPath('data.status', 'assigned');
        $this->actingAs($this->customer)->getJson('/api/v1/platform/compliance/cases')->assertForbidden();
        $this->assertDatabaseHas('case_events', ['compliance_case_id' => $caseId, 'event_type' => 'status_changed']);
    }

    public function test_customer_security_records_are_owner_scoped(): void
    {
        DB::table('active_sessions')->insert(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $this->customer->id, 'session_hash' => hash('sha256', 'customer'), 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'last_seen_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $other = User::create(['name' => 'Other', 'email' => 'other-security@example.com', 'password' => 'password-password', 'account_status' => 'active', 'user_type' => 'customer', 'email_verified_at' => now()]);
        DB::table('active_sessions')->insert(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $other->id, 'session_hash' => hash('sha256', 'other'), 'ip_address' => '10.0.0.2', 'user_agent' => 'other', 'last_seen_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->customer)->getJson('/api/v1/security/sessions')->assertOk()->assertJsonCount(1, 'data')->assertJsonMissing(['ip_address' => '10.0.0.2']);
    }

    private function makeAdmin(): User
    {
        $user = User::create(['name' => 'Risk Analyst', 'email' => 'risk-admin@example.com', 'password' => 'password-password', 'account_status' => 'active', 'user_type' => 'staff', 'email_verified_at' => now()]);
        $user->forceFill(['is_active' => true])->save();
        DB::table('mfa_methods')->insert(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $user->id, 'type' => 'totp', 'secret_encrypted' => 'test', 'is_primary' => true, 'verified_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $role = PlatformRole::where('name', 'platform_super_admin')->firstOrFail();
        PlatformAdminMembership::create(['user_id' => $user->id, 'platform_role_id' => $role->id, 'status' => 'active']);
        return $user;
    }
}
