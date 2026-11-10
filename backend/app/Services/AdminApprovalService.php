<?php

namespace App\Services;

use App\Models\AdminApprovalRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AdminApprovalService
{
    public function __construct(private PlatformAdminAccessService $access, private PlatformAuditLogger $audit) {}

    public function request(User $user, string $permission, string $action, ?string $resourceType, ?string $resourceId, string $reason, string $key, int $required = 1): AdminApprovalRequest
    {
        $this->access->assert($user, $permission);
        if ($key === '') throw ValidationException::withMessages(['idempotency_key' => 'Idempotency-Key is required.']);
        return AdminApprovalRequest::firstOrCreate(['requested_by' => $user->id, 'idempotency_key' => $key], ['action' => $action, 'resource_type' => $resourceType, 'resource_id' => $resourceId, 'status' => 'pending', 'required_decisions' => max(1, min(2, $required)), 'expires_at' => now()->addHours(24), 'reason' => $reason, 'metadata' => ['sandbox_only' => true]]);
    }

    public function decide(User $user, string $permission, AdminApprovalRequest $approval, string $decision, string $reason, string $challengeId, string $challengeCode): AdminApprovalRequest
    {
        $this->access->assertSensitive($user, $permission, $challengeId, $challengeCode);
        return DB::transaction(function () use ($user, $approval, $decision, $reason): AdminApprovalRequest {
            $locked = AdminApprovalRequest::whereKey($approval->id)->lockForUpdate()->firstOrFail();
            if ($locked->requested_by === $user->id) throw ValidationException::withMessages(['approval' => 'The maker cannot approve their own request.']);
            if (!in_array($decision, ['approved', 'rejected'], true) || $locked->status !== 'pending' || now()->greaterThan($locked->expires_at)) throw ValidationException::withMessages(['approval' => 'Approval is no longer actionable.']);
            $locked->decisions()->create(['decided_by' => $user->id, 'decision' => $decision, 'reason' => $reason]);
            if ($decision === 'rejected') $locked->update(['status' => 'rejected']);
            elseif ($locked->decisions()->where('decision', 'approved')->count() >= $locked->required_decisions) $locked->update(['status' => 'approved']);
            $this->audit->record('admin.approval.'.$decision, AdminApprovalRequest::class, $locked->id, request(), [], ['status' => $locked->status], $decision, $locked->id, ['sandbox_only' => true]);
            return $locked->refresh()->load('decisions');
        });
    }
}
