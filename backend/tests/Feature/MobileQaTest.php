<?php

namespace Tests\Feature;

use App\Domain\QA\MobileQaService;
use App\Models\MobileBuild;
use App\Models\QaTestRun;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class MobileQaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
    }

    public function test_mobile_readiness_is_blocked_without_evidence(): void
    {
        $overview = app(MobileQaService::class)->overview();

        $this->assertTrue($overview['sandbox_only']);
        $this->assertCount(8, $overview['release_gates']);
        $this->assertTrue(collect($overview['release_gates'])->every(fn (array $gate): bool => $gate['status'] === 'blocked'));
    }

    public function test_passing_mobile_artifacts_ready_only_their_specific_gates(): void
    {
        QaTestRun::create(['suite' => 'mobile-functional', 'category' => 'mobile_functional', 'status' => 'passed', 'environment' => 'isolated-test', 'finished_at' => now(), 'artifact_uri' => 'ci://mobile/functionality']);
        MobileBuild::create(['platform' => 'android', 'channel' => 'sandbox-beta', 'version_name' => '0.1.0', 'version_code' => 1, 'status' => 'passed', 'finished_at' => now(), 'artifact_uri' => 'ci://mobile/android']);

        $gates = collect(app(MobileQaService::class)->gates());

        $this->assertSame('ready', $gates->firstWhere('gate_key', 'mobile_critical_tests')['status']);
        $this->assertSame('ready', $gates->firstWhere('gate_key', 'android_build')['status']);
        $this->assertSame('blocked', $gates->firstWhere('gate_key', 'ios_build')['status']);
        $this->assertSame('blocked', $gates->firstWhere('gate_key', 'beta_approval')['status']);
    }

    public function test_mobile_overview_requires_authentication(): void
    {
        $this->getJson('/api/v1/platform/mobile/overview')->assertUnauthorized();
    }
}
