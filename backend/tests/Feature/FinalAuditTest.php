<?php

namespace Tests\Feature;

use App\Domain\QA\QaAssessmentService;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class FinalAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
    }

    public function test_quality_overview_is_evidence_backed_and_blocked_without_financial_evidence(): void
    {
        $overview = app(QaAssessmentService::class)->overview();

        $this->assertTrue($overview['sandbox_only']);
        $this->assertArrayHasKey('module_coverage', $overview);
        $this->assertArrayHasKey('feature_inventory', $overview);
        $this->assertArrayHasKey('maintenance_backlog', $overview);
        $this->assertSame('blocked', $overview['financial_integrity']['status']);
        $this->assertTrue(collect($overview['release_gates'])->contains(fn (array $gate): bool => $gate['status'] === 'blocked'));
    }

    public function test_final_audit_endpoints_require_platform_authentication(): void
    {
        $this->getJson('/api/v1/platform/qa/module-coverage')->assertUnauthorized();
        $this->getJson('/api/v1/platform/qa/financial-integrity')->assertUnauthorized();
        $this->getJson('/api/v1/platform/qa/infrastructure')->assertUnauthorized();
    }
}
