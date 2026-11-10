<?php
namespace App\Domain\Compliance;

use App\Models\CaseEvent;
use App\Models\ComplianceCase;
use App\Services\PlatformAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class ComplianceCaseService
{
    private array $transitions = ['open' => ['assigned', 'investigating', 'closed'], 'assigned' => ['investigating', 'awaiting_information', 'escalated'], 'investigating' => ['awaiting_information', 'escalated', 'resolved'], 'awaiting_information' => ['investigating', 'escalated'], 'escalated' => ['investigating', 'resolved'], 'resolved' => ['closed'], 'closed' => []];
    public function create(array $data, Request $request): ComplianceCase
    {
        $case = ComplianceCase::create($data + ['opened_by' => $request->user()->id, 'status' => 'open', 'metadata' => ['sandbox_only' => true]]);
        $this->event($case, $request, 'case_opened', $case->reason);
        app(PlatformAuditLogger::class)->record('compliance.case.opened', ComplianceCase::class, $case->id, $request, [], ['status' => $case->status, 'priority' => $case->priority], 'recorded', null, ['sandbox_only' => true]);
        return $case->load(['events', 'evidence']);
    }
    public function transition(ComplianceCase $case, string $status, string $notes, Request $request): ComplianceCase
    {
        if (!in_array($status, $this->transitions[$case->status] ?? [], true)) throw ValidationException::withMessages(['status' => 'Invalid compliance case transition.']);
        if (in_array($status, ['resolved', 'closed', 'escalated'], true) && trim($notes) === '') throw ValidationException::withMessages(['notes' => 'A reason is required for this decision.']);
        $previous = ['status' => $case->status]; $case->update(['status' => $status]); $this->event($case, $request, 'status_changed', $notes); app(PlatformAuditLogger::class)->record('compliance.case.status_changed', ComplianceCase::class, $case->id, $request, $previous, ['status' => $status], 'recorded', null, ['reason' => $notes, 'sandbox_only' => true]); return $case->load(['events', 'evidence']);
    }
    private function event(ComplianceCase $case, Request $request, string $type, ?string $notes): void { CaseEvent::create(['compliance_case_id' => $case->id, 'actor_id' => $request->user()->id, 'event_type' => $type, 'notes' => $notes, 'metadata' => ['sandbox_only' => true], 'created_at' => now()]); }
}
