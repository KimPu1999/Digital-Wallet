<?php

namespace Tests\Feature;

use App\Domain\Enterprise\EnterpriseOperationsService;
use App\Models\PlatformFeatureFlag;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class EnterpriseOperationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
    }

    public function test_enterprise_overview_is_real_data_and_sandbox_only(): void
    {
        $overview = app(EnterpriseOperationsService::class)->overview();

        $this->assertTrue($overview['sandbox_only']);
        $this->assertSame(0, $overview['customers']);
        $this->assertSame(0, $overview['active_incidents']);
        $this->assertArrayHasKey('generated_at', $overview);
    }

    public function test_feature_rollout_is_deterministic_and_disabled_by_default(): void
    {
        $flag = PlatformFeatureFlag::create(['flag_key' => 'enterprise.test', 'enabled' => false, 'environment' => 'sandbox', 'category' => 'product', 'rollout_percentage' => 100]);
        $service = app(EnterpriseOperationsService::class);

        $this->assertFalse($service->featureEnabled($flag->flag_key, 'subject-1'));
        $flag->update(['enabled' => true, 'rollout_percentage' => 50]);
        $first = $service->featureEnabled($flag->flag_key, 'subject-1');
        $second = $service->featureEnabled($flag->flag_key, 'subject-1');
        $this->assertSame($first, $second);
    }

    public function test_enterprise_overview_requires_platform_authentication(): void
    {
        $this->getJson('/api/v1/platform/enterprise/overview')->assertUnauthorized();
    }

    public function test_customer_lifecycle_directory_redacts_credentials(): void
    {
        User::create(['name' => 'Lifecycle User', 'email' => 'lifecycle@example.com', 'password' => 'secret-password', 'account_status' => 'active', 'user_type' => 'customer']);
        $row = app(EnterpriseOperationsService::class)->lifecycle('customers')->items()[0];

        $this->assertArrayNotHasKey('password', (array) $row);
        $this->assertArrayNotHasKey('remember_token', (array) $row);
    }
}
