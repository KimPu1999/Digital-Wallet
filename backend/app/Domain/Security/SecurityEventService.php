<?php
namespace App\Domain\Security;

use App\Models\AuthSecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class SecurityEventService
{
    public function record(?string $userId, string $eventType, Request $request, string $severity = 'low', array $metadata = []): AuthSecurityEvent
    {
        return AuthSecurityEvent::create(['user_id' => $userId, 'event_type' => $eventType, 'severity' => $severity, 'ip_address' => $request->ip(), 'correlation_id' => $request->header('X-Correlation-ID') ?: (string) Str::uuid(), 'metadata' => $metadata + ['sandbox_only' => true], 'occurred_at' => now()]);
    }
}
