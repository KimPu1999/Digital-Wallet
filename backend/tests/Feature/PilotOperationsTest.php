<?php

namespace Tests\Feature;

use App\Domain\PilotOperations\PilotOperationsService;
use App\Models\PilotProgram;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class PilotOperationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
    }

    public function test_pilot_programs_are_sandbox_drafts(): void
    {
        $actor = User::create(['name' => 'Pilot Admin', 'email' => 'pilot-admin@example.com', 'password' => 'secret-password']);
        $program = $this->service()->createProgram($this->payload(), $actor->id);

        $this->assertSame('sandbox', $program->environment);
        $this->assertSame('draft', $program->status);
        $this->assertTrue($program->sandbox_only);
        $this->assertSame(1, DB::table('pilot_scope_versions')->where('pilot_program_id', $program->id)->count());
        $overview = $this->service()->overview($program->id);
        $this->assertTrue($overview['sandbox_only']);
        $this->assertSame(0, $overview['sandbox_transaction_count']);
    }

    public function test_non_sandbox_pilot_is_rejected(): void
    {
        $actor = User::create(['name' => 'Pilot Admin', 'email' => 'pilot-admin-2@example.com', 'password' => 'secret-password']);

        $this->expectException(ValidationException::class);
        $this->service()->createProgram($this->payload(['environment' => 'production']), $actor->id);
    }

    public function test_participant_enrollment_is_unique_and_checks_email_verification(): void
    {
        $actor = User::create(['name' => 'Pilot Admin', 'email' => 'pilot-admin-3@example.com', 'password' => 'secret-password']);
        $customer = User::create(['name' => 'Sandbox Customer', 'email' => 'customer@example.com', 'password' => 'secret-password', 'account_status' => 'active']);
        $program = $this->service()->createProgram($this->payload(), $actor->id);
        $program->update(['status' => 'planned']);

        $result = $this->service()->enroll($program, ['subject_type' => 'customer', 'subject_id' => $customer->id], $actor->id);
        $this->assertSame('ineligible', DB::table('pilot_participants')->where('id', $result['participant']->id)->value('eligibility_status'));
        $this->assertSame('failed', DB::table('pilot_eligibility_checks')->where('participant_id', $result['participant']->id)->where('check_type', 'email_verification')->value('status'));

        $this->expectException(ValidationException::class);
        $this->service()->enroll($program, ['subject_type' => 'customer', 'subject_id' => $customer->id], $actor->id);
    }

    public function test_kyc_requirement_blocks_unverified_customer(): void
    {
        DB::table('countries')->insert(['code' => 'MM', 'name' => 'Myanmar', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('country_capabilities')->insert(['country_code' => 'MM', 'registration_enabled' => true, 'kyc_enabled' => true, 'wallet_enabled' => true, 'created_at' => now(), 'updated_at' => now()]);
        $actor = User::create(['name' => 'Pilot Admin', 'email' => 'pilot-admin-4@example.com', 'password' => 'secret-password']);
        $customer = User::create(['name' => 'Verified Email', 'email' => 'verified@example.com', 'password' => 'secret-password', 'country_code' => 'MM', 'account_status' => 'active']);
        $customer->forceFill(['email_verified_at' => now()])->save();
        $program = $this->service()->createProgram($this->payload(), $actor->id);
        $program->update(['status' => 'planned']);

        $result = $this->service()->enroll($program, ['subject_type' => 'customer', 'subject_id' => $customer->id], $actor->id);
        $this->assertSame('failed', DB::table('pilot_eligibility_checks')->where('participant_id', $result['participant']->id)->where('check_type', 'kyc_status')->value('status'));
    }

    public function test_limits_are_currency_specific_and_enforced(): void
    {
        DB::table('currencies')->insert([
            ['code' => 'USD', 'name' => 'US Dollar', 'decimals' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'MMK', 'name' => 'Myanmar Kyat', 'decimals' => 0, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $actor = User::create(['name' => 'Pilot Admin', 'email' => 'pilot-admin-5@example.com', 'password' => 'secret-password']);
        $program = $this->service()->createProgram($this->payload(['supported_currencies' => ['USD']]), $actor->id);
        DB::table('pilot_limits')->insert(['id' => (string) \Illuminate\Support\Str::uuid(), 'pilot_program_id' => $program->id, 'currency_code' => 'USD', 'max_transaction_minor' => 1000, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

        $this->expectException(ValidationException::class);
        $this->service()->assertWithinLimits($program, 'USD', 1001, 'transfer');
    }

    public function test_unauthenticated_pilot_routes_are_rejected(): void
    {
        $this->getJson('/api/v1/platform/pilots')->assertUnauthorized();
        $this->postJson('/api/v1/platform/pilots', [])->assertUnauthorized();
    }

    private function service(): PilotOperationsService
    {
        return app(PilotOperationsService::class);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Sandbox Wallet Pilot',
            'objective' => 'Measure controlled sandbox operations and reconciliation quality.',
            'environment' => 'sandbox',
            'jurisdictions' => ['MM'],
            'supported_currencies' => ['USD', 'MMK'],
            'allowed_features' => ['sandbox_wallet_checkout', 'read_only_reporting'],
            'provider_capabilities' => [],
            'transaction_types' => ['wallet_checkout'],
        ], $overrides);
    }
}
