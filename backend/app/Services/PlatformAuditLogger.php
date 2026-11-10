<?php

namespace App\Services;

use App\Models\AdminSecurityEvent;
use App\Models\PlatformAuditEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PlatformAuditLogger
{
    public function record(string $action, ?string $resourceType, ?string $resourceId, ?Request $request = null, array $previous = [], array $next = [], ?string $decision = null, ?string $approvalId = null, array $metadata = []): PlatformAuditEvent
    {
        return PlatformAuditEvent::create(['actor_id' => $request?->user()?->id, 'action' => $action, 'resource_type' => $resourceType, 'resource_id' => $resourceId, 'correlation_id' => $request?->header('X-Correlation-ID') ?: (string) Str::uuid(), 'decision' => $decision, 'approval_request_id' => $approvalId, 'previous_state' => $previous, 'new_state' => $next, 'metadata' => $metadata, 'created_at' => now()]);
    }

    public function security(string $event, Request $request, array $metadata = []): AdminSecurityEvent
    {
        return AdminSecurityEvent::create(['actor_id' => $request->user()?->id, 'event_type' => $event, 'correlation_id' => $request->header('X-Correlation-ID') ?: (string) Str::uuid(), 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'metadata' => $metadata, 'created_at' => now()]);
    }
}
