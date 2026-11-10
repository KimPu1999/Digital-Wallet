<?php

namespace Tests\Feature;

use App\Domain\QA\QaAssessmentService;
use App\Models\QaTestRun;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class QaAssessmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
    }

    public function test_missing_evidence_blocks_release_gates(): void
    {
        $overview = app(QaAssessmentService::class)->overview();

        $this->assertSame(0, $overview['test_runs']['total']);
        $this->assertCount(6, $overview['release_gates']);
        $this->assertTrue(collect($overview['release_gates'])->every(fn (array $gate): bool => $gate['status'] === 'blocked'));
    }

    public function test_latest_passing_evidence_readies_its_gate(): void
    {
        QaTestRun::create([
            'suite' => 'financial-integrity',
            'category' => 'financial_integrity',
            'status' => 'passed',
            'environment' => 'isolated-test',
            'finished_at' => now(),
            'summary' => ['assertions' => 12],
            'artifact_uri' => 'ci://run/financial-integrity',
        ]);

        $gate = collect(app(QaAssessmentService::class)->gates())->firstWhere('gate_key', 'financial_integrity');

        $this->assertSame('ready', $gate['status']);
        $this->assertSame('ci://run/financial-integrity', $gate['evidence_reference']);
    }

    public function test_failed_latest_evidence_does_not_fall_back_to_older_success(): void
    {
        QaTestRun::create(['suite' => 'security', 'category' => 'security', 'status' => 'passed', 'environment' => 'isolated-test', 'finished_at' => now()->subMinute()]);
        QaTestRun::create(['suite' => 'security', 'category' => 'security', 'status' => 'failed', 'environment' => 'isolated-test', 'finished_at' => now()]);

        $gate = collect(app(QaAssessmentService::class)->gates())->firstWhere('gate_key', 'authorization_regression');

        $this->assertSame('blocked', $gate['status']);
        $this->assertSame('Latest evidence did not pass.', $gate['reason']);
    }

    public function test_qa_overview_requires_authentication(): void
    {
        $this->getJson('/api/v1/platform/qa/overview')->assertUnauthorized();
    }
}
