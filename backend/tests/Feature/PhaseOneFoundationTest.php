<?php
namespace Tests\Feature;
use Tests\TestCase;
class PhaseOneFoundationTest extends TestCase { public function test_health_endpoint_is_available(): void { $this->getJson('/api/v1/health')->assertOk()->assertJsonPath('demo_only', true); } public function test_protected_wallet_endpoint_rejects_guests(): void { $this->getJson('/api/v1/wallets')->assertUnauthorized(); } }
