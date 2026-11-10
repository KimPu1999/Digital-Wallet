<?php

namespace App\Services;

use App\Models\FinancialAuditEvent;
use Illuminate\Http\Request;

final class FinancialAuditLogger
{
    public function record(string $action, ?string $entityType, ?string $entityId, array $metadata = [], ?Request $request = null, ?string $actorId = null): FinancialAuditEvent
    {
        return FinancialAuditEvent::create([
            'action' => $action,
            'actor_id' => $actorId ?? $request?->user()?->id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'request_id' => $request?->header('X-Request-ID'),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
