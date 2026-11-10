<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\PilotOperations\PilotOperationsService;
use App\Models\PilotProgram;
use App\Services\AdminApprovalService;
use App\Services\PlatformAdminAccessService;
use App\Services\PlatformAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class PlatformPilotController
{
    public function __construct(private PilotOperationsService $pilots, private PlatformAdminAccessService $access) {}

    public function index(Request $request): JsonResponse { $this->assert($request, 'dashboard.read'); return $this->page($this->pilots->programs()); }
    public function store(Request $request): JsonResponse
    {
        $this->assert($request, 'settings.manage');
        $data = $request->validate($this->programRules());
        $program = $this->pilots->createProgram($data, $request->user()->id);
        $this->audit('pilot.created', $program, $request, ['status' => 'draft']);
        return response()->json(['data' => $program, 'message' => 'Sandbox pilot draft created. Activation and production financial operations remain unavailable.', 'sandbox_only' => true], 201);
    }
    public function show(Request $request, string $id): JsonResponse { $this->assert($request, 'dashboard.read'); return $this->ok($this->pilots->program($id)); }
    public function overview(Request $request, string $id): JsonResponse { $this->assert($request, 'dashboard.read'); return $this->ok($this->pilots->overview($id)); }
    public function update(Request $request, string $id): JsonResponse
    {
        $this->assert($request, 'settings.manage');
        $program = PilotProgram::findOrFail($id);
        $data = $request->validate(['name' => ['sometimes', 'string', 'max:180'], 'objective' => ['sometimes', 'string', 'max:5000'], 'jurisdictions' => ['sometimes', 'array'], 'supported_currencies' => ['sometimes', 'array', 'min:1'], 'supported_currencies.*' => ['string', Rule::in(['USD', 'EUR', 'GBP', 'SGD', 'THB', 'MMK'])], 'allowed_features' => ['sometimes', 'array'], 'allowed_features.*' => ['string', Rule::in(['sandbox_wallet_checkout', 'sandbox_provider_checkout', 'sandbox_qr', 'sandbox_fx_quote', 'sandbox_transfer_simulation', 'read_only_reporting'])], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at']]);
        $updated = $this->pilots->updateDraft($program, $data);
        $this->audit('pilot.updated', $updated, $request, ['status' => $updated->status]);
        return $this->ok(['program' => $updated]);
    }
    public function cohorts(Request $request, string $id): JsonResponse { $this->assert($request, 'dashboard.read'); return $this->page($this->pilots->cohorts($id)); }
    public function createCohort(Request $request, string $id): JsonResponse
    {
        $this->assert($request, 'settings.manage');
        $program = PilotProgram::findOrFail($id);
        $data = $request->validate(['name' => ['required', 'string', 'max:160'], 'cohort_type' => ['required', 'in:customer,merchant,internal'], 'eligibility_requirements' => ['nullable', 'array'], 'max_participants' => ['nullable', 'integer', 'min:1', 'max:100000']]);
        $cohort = $this->pilots->createCohort($program, $data, $request->user()->id);
        $this->audit('pilot.cohort.created', 'pilot_cohorts', $request, ['pilot_program_id' => $id], [], 'recorded', null, $cohort->id);
        return response()->json(['data' => $cohort, 'sandbox_only' => true], 201);
    }
    public function participants(Request $request, string $id): JsonResponse { $this->assert($request, 'dashboard.read'); return $this->page($this->pilots->participants($id)); }
    public function addParticipant(Request $request, string $id): JsonResponse
    {
        $this->assert($request, 'settings.manage');
        $program = PilotProgram::findOrFail($id);
        $data = $request->validate(['cohort_id' => ['nullable', 'uuid'], 'subject_type' => ['required', 'in:customer,merchant,internal'], 'subject_id' => ['required', 'uuid']]);
        if ($data['subject_type'] === 'customer') $this->validateExists('users', $data['subject_id'], 'subject_id');
        if ($data['subject_type'] === 'merchant') $this->validateExists('merchants', $data['subject_id'], 'subject_id');
        $result = $this->pilots->enroll($program, $data, $request->user()->id);
        $this->audit('pilot.participant.enrolled', 'pilot_participants', $request, ['pilot_program_id' => $id, 'eligibility_status' => $result['participant']->eligibility_status], [], 'recorded', null, $result['participant']->id);
        return response()->json(['data' => $result, 'sandbox_only' => true], 201);
    }
    public function eligibility(Request $request, string $id): JsonResponse { $this->assert($request, 'compliance.read'); return $this->page($this->pilots->eligibility($id)); }
    public function limits(Request $request, string $id): JsonResponse { $this->assert($request, 'financial.read'); return $this->page($this->pilots->limits($id)); }
    public function activity(Request $request, string $id): JsonResponse { $this->assert($request, 'financial.read'); return $this->page($this->pilots->activity($id)); }
    public function monitoring(Request $request, string $id): JsonResponse { $this->assert($request, 'dashboard.read'); return $this->page($this->pilots->monitoring($id)); }
    public function transactions(Request $request, string $id): JsonResponse { $this->assert($request, 'financial.read'); return $this->page($this->pilots->activity($id)); }
    public function reconciliation(Request $request, string $id): JsonResponse { $this->assert($request, 'reconciliation.read'); return $this->page($this->pilots->reconciliation($id)); }
    public function exceptions(Request $request, string $id): JsonResponse { $this->assert($request, 'reconciliation.read'); return $this->page($this->pilots->exceptions($id)); }
    public function reports(Request $request, string $id): JsonResponse { $this->assert($request, 'reports.export'); return $this->ok($this->pilots->reports($id)); }
    public function evaluations(Request $request, string $id): JsonResponse { $this->assert($request, 'dashboard.read'); return $this->page($this->pilots->evaluations($id)); }
    public function decisions(Request $request, string $id): JsonResponse { $this->assert($request, 'approvals.request'); return $this->page($this->pilots->decisions($id)); }

    public function createEvaluation(Request $request, string $id): JsonResponse
    {
        $this->assert($request, 'compliance.decide');
        PilotProgram::findOrFail($id);
        $data = $request->validate(['period_start' => ['required', 'date'], 'period_end' => ['required', 'date', 'after_or_equal:period_start'], 'criteria_comparison' => ['nullable', 'array'], 'operational_findings' => ['nullable', 'array'], 'financial_integrity_findings' => ['nullable', 'array'], 'security_findings' => ['nullable', 'array'], 'compliance_findings' => ['nullable', 'array'], 'participant_feedback_summary' => ['nullable', 'string', 'max:5000'], 'outstanding_issues' => ['nullable', 'string', 'max:5000'], 'recommendations' => ['nullable', 'string', 'max:5000'], 'evidence_reference' => ['nullable', 'string', 'max:500']]);
        $evaluationId = (string) Str::uuid();
        DB::table('pilot_evaluations')->insert($data + ['id' => $evaluationId, 'pilot_program_id' => $id, 'status' => 'draft', 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->audit('pilot.evaluation.created', 'pilot_evaluations', $evaluationId, $request, ['status' => 'draft']);
        return response()->json(['data' => DB::table('pilot_evaluations')->where('id', $evaluationId)->first(), 'sandbox_only' => true], 201);
    }

    public function createDecision(Request $request, string $id, AdminApprovalService $approvals): JsonResponse
    {
        $this->assert($request, 'approvals.request');
        PilotProgram::findOrFail($id);
        $data = $request->validate(['decision' => ['required', 'in:continue_sandbox,extend_sandbox,pause,close,further_review'], 'reason' => ['required', 'string', 'max:2000']]);
        $key = (string) $request->header('Idempotency-Key');
        if ($key === '') throw ValidationException::withMessages(['idempotency_key' => 'Idempotency-Key is required.']);
        $approval = $approvals->request($request->user(), 'approvals.request', 'pilot.decision', PilotProgram::class, $id, $data['reason'], $key, 2);
        $decision = DB::table('pilot_decisions')->where('approval_request_id', $approval->id)->first();
        if (!$decision) {
            $decisionId = (string) Str::uuid();
            DB::table('pilot_decisions')->insert(['id' => $decisionId, 'pilot_program_id' => $id, 'decision' => $data['decision'], 'status' => 'pending', 'reason' => $data['reason'], 'requested_by' => $request->user()->id, 'approval_request_id' => $approval->id, 'created_at' => now(), 'updated_at' => now()]);
            $decision = DB::table('pilot_decisions')->where('id', $decisionId)->first();
        }
        $this->audit('pilot.decision.requested', 'pilot_decisions', $decision->id, $request, ['status' => 'pending'], ['decision' => $decision->decision], 'pending_approval', $approval->id);
        return response()->json(['data' => ['decision' => $decision, 'approval' => $approval], 'message' => 'Pilot decision awaits independent approval and cannot enable live financial execution.', 'sandbox_only' => true], 202);
    }

    public function pause(Request $request, string $id): JsonResponse
    {
        $program = PilotProgram::findOrFail($id);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000'], 'challenge_id' => ['required', 'string'], 'challenge_code' => ['required', 'string']]);
        $this->access->assertSensitive($request->user(), 'settings.manage', $data['challenge_id'], $data['challenge_code']);
        $paused = $this->pilots->pause($program, $request->user()->id, $data['reason']);
        $this->audit('pilot.paused', $paused, $request, ['status' => $program->status], ['status' => 'paused'], 'approved');
        return $this->ok(['program' => $paused, 'message' => 'Pilot activity paused. No production financial capability was changed.']);
    }

    public function closure(Request $request, string $id, AdminApprovalService $approvals): JsonResponse
    {
        $this->assert($request, 'approvals.request');
        $program = PilotProgram::findOrFail($id);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $key = (string) $request->header('Idempotency-Key');
        if ($key === '') throw ValidationException::withMessages(['idempotency_key' => 'Idempotency-Key is required.']);
        $closure = DB::table('pilot_closure_records')->where('pilot_program_id', $id)->first();
        if ($closure) return response()->json(['data' => ['closure' => $closure], 'message' => 'Pilot closure request already exists.', 'sandbox_only' => true], 202);
        $approval = $approvals->request($request->user(), 'approvals.request', 'pilot.closure', PilotProgram::class, $id, $data['reason'], $key, 2);
        $closureId = (string) Str::uuid();
        DB::transaction(function () use ($closureId, $id, $approval, $request): void { DB::table('pilot_closure_records')->insert(['id' => $closureId, 'pilot_program_id' => $id, 'status' => 'pending', 'approval_request_id' => $approval->id, 'created_at' => now(), 'updated_at' => now()]); DB::table('pilot_programs')->where('id', $id)->update(['status' => 'closure_requested', 'updated_at' => now()]); });
        $this->audit('pilot.closure.requested', 'pilot_closure_records', $closureId, $request, ['status' => 'pending'], ['pilot_status' => 'closure_requested'], 'pending_approval', $approval->id);
        return response()->json(['data' => ['closure' => DB::table('pilot_closure_records')->where('id', $closureId)->first(), 'approval' => $approval], 'message' => 'Pilot closure awaits independent approval. Financial and audit records will be preserved.', 'sandbox_only' => true], 202);
    }

    private function programRules(): array { return ['name' => ['required', 'string', 'max:180'], 'objective' => ['required', 'string', 'max:5000'], 'environment' => ['nullable', 'in:sandbox'], 'jurisdictions' => ['nullable', 'array'], 'supported_currencies' => ['required', 'array', 'min:1'], 'supported_currencies.*' => ['string', Rule::in(['USD', 'EUR', 'GBP', 'SGD', 'THB', 'MMK'])], 'allowed_features' => ['required', 'array', 'min:1'], 'allowed_features.*' => ['string', Rule::in(['sandbox_wallet_checkout', 'sandbox_provider_checkout', 'sandbox_qr', 'sandbox_fx_quote', 'sandbox_transfer_simulation', 'read_only_reporting'])], 'provider_capabilities' => ['nullable', 'array'], 'transaction_types' => ['nullable', 'array'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'], 'owner_id' => ['nullable', 'uuid', 'exists:users,id'], 'reviewer_id' => ['nullable', 'uuid', 'exists:users,id'], 'pilot_authorization_id' => ['nullable', 'uuid', 'exists:pilot_authorizations,id']]; }
    private function validateExists(string $table, string $id, string $field): void { if (!DB::table($table)->where('id', $id)->exists()) throw ValidationException::withMessages([$field => 'The referenced subject does not exist.']); }
    private function assert(Request $request, string $permission): void { $this->access->assert($request->user(), $permission); }
    private function ok(array $data): JsonResponse { return response()->json(['data' => $data, 'sandbox_only' => true]); }
    private function page($page): JsonResponse { return response()->json(['data' => $page->items(), 'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()], 'sandbox_only' => true]); }
    private function audit(string $action, mixed $resource, Request $request, array $next = [], array $previous = [], ?string $decision = 'recorded', ?string $approval = null, ?string $resourceId = null): void { $type = is_object($resource) ? $resource::class : (is_string($resource) ? $resource : null); $id = is_object($resource) ? (string) $resource->id : $resourceId; app(PlatformAuditLogger::class)->record($action, $type, $id, $request, $previous, $next, $decision, $approval, ['sandbox_only' => true]); }
}
