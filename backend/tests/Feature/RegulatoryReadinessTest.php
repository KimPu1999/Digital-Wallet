<?php

namespace Tests\Feature;

use App\Domain\RegulatoryReadiness\RegulatoryReadinessService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class RegulatoryReadinessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
    }

    public function test_launch_readiness_is_blocked_without_verified_evidence(): void
    {
        $readiness = app(RegulatoryReadinessService::class)->readiness();

        $this->assertSame('blocked', $readiness['status']);
        $this->assertCount(12, $readiness['gates']);
        $this->assertFalse($readiness['live_financial_execution_enabled']);
    }

    public function test_safeguarding_reporting_keeps_currency_amounts_separate(): void
    {
        DB::table('currencies')->insert([
            ['code' => 'USD', 'name' => 'US Dollar', 'decimals' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'MMK', 'name' => 'Myanmar Kyat', 'decimals' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('safeguarding_reconciliations')->insert([
            ['id' => '11111111-1111-4111-8111-111111111111', 'run_reference' => 'SAFE-USD-1', 'currency_code' => 'USD', 'expected_amount_minor' => 1000, 'verified_external_amount_minor' => 1000, 'variance_minor' => 0, 'status' => 'reconciled', 'created_at' => now(), 'updated_at' => now()],
            ['id' => '22222222-2222-4222-8222-222222222222', 'run_reference' => 'SAFE-MMK-1', 'currency_code' => 'MMK', 'expected_amount_minor' => 2000, 'verified_external_amount_minor' => 1900, 'variance_minor' => 100, 'status' => 'exception', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $rows = app(RegulatoryReadinessService::class)->safeguardingOverview()['by_currency'];

        $this->assertCount(2, $rows);
        $this->assertSame(['MMK', 'USD'], $rows->pluck('currency_code')->all());
        $this->assertSame('1000', (string) $rows->firstWhere('currency_code', 'USD')['expected_amount_minor']);
        $this->assertSame('2000', (string) $rows->firstWhere('currency_code', 'MMK')['expected_amount_minor']);
    }

    public function test_regulatory_endpoints_require_platform_authentication(): void
    {
        $this->getJson('/api/v1/platform/regulatory/overview')->assertUnauthorized();
        $this->getJson('/api/v1/platform/safeguarding/overview')->assertUnauthorized();
        $this->getJson('/api/v1/platform/launch/readiness')->assertUnauthorized();
    }

    public function test_partner_production_capability_is_not_created_by_directory_records(): void
    {
        DB::table('financial_partners')->insert([
            'id' => '33333333-3333-4333-8333-333333333333',
            'name' => 'Sandbox partner record',
            'partner_type' => 'psp',
            'onboarding_status' => 'prospective',
            'contract_status' => 'not_reviewed',
            'api_readiness' => 'not_configured',
            'risk_classification' => 'unassessed',
            'capabilities' => json_encode([]),
            'sandbox_only' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $partner = DB::table('financial_partners')->first();
        $this->assertTrue((bool) $partner->sandbox_only);
        $this->assertSame('prospective', $partner->onboarding_status);
        $this->assertSame(0, DB::table('partner_capability_approvals')->where('partner_id', $partner->id)->where('environment', 'production')->count());
    }
}
