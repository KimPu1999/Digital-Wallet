<?php
namespace App\Domain\Fraud;

use App\Domain\AML\RiskRuleEngine;
use App\Models\AuthSecurityEvent;
use App\Models\RiskAlert;
use App\Models\RiskEvaluation;

final class FraudDetectionService
{
    public function __construct(private RiskRuleEngine $engine) {}
    public function inspectAuthentication(string $userId, array $context): RiskEvaluation
    {
        return $this->engine->evaluate('user', $userId, 'failed_auth_velocity', $context + ['event_type' => 'authentication']);
    }
    public function recordNewDevice(string $userId, array $context): AuthSecurityEvent
    {
        $event = AuthSecurityEvent::create(['user_id' => $userId, 'event_type' => 'new_device', 'severity' => 'medium', 'ip_address' => $context['ip_address'] ?? null, 'device_key_hash' => $context['device_key_hash'] ?? null, 'correlation_id' => $context['correlation_id'] ?? null, 'metadata' => ['sensitive_operation' => (bool) ($context['sensitive_operation'] ?? false), 'sandbox_only' => true], 'occurred_at' => now()]);
        RiskAlert::firstOrCreate(['deduplication_key' => 'new-device|'.$userId.'|'.($context['device_key_hash'] ?? 'unknown')], ['subject_type' => 'user', 'subject_id' => $userId, 'category' => 'device_risk', 'severity' => 'medium', 'status' => 'open', 'reason' => 'New device observed for the account.', 'evidence' => ['event_id' => $event->id, 'sandbox_only' => true]]);
        return $event;
    }
}
