<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\RegulatoryReadiness\RegulatoryReadinessService;
use App\Models\PilotAuthorization;
use App\Services\AdminApprovalService;
use App\Services\PlatformAdminAccessService;
use App\Services\PlatformAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class PlatformRegulatoryController
{
    public function __construct(private RegulatoryReadinessService $readiness, private PlatformAdminAccessService $access) {}

    public function overview(Request $request): JsonResponse { $this->assert($request, 'dashboard.read'); return $this->ok($this->readiness->overview()); }
    public function jurisdictions(Request $request): JsonResponse { $this->assert($request, 'compliance.read'); return $this->page($this->readiness->jurisdictions()); }
    public function activities(Request $request): JsonResponse { $this->assert($request, 'compliance.read'); return $this->page($this->readiness->activities()); }
    public function licenses(Request $request): JsonResponse { $this->assert($request, 'compliance.read'); return $this->page($this->readiness->licenses()); }
    public function obligations(Request $request): JsonResponse { $this->assert($request, 'compliance.read'); return $this->page($this->readiness->obligations()); }
    public function partners(Request $request): JsonResponse { $this->assert($request, 'merchants.read'); return $this->page($this->readiness->partners()); }

    public function createPartner(Request $request): JsonResponse
    {
        $this->assert($request, 'settings.manage');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'partner_type' => ['required', 'in:bank,emi,psp,fx_provider,remittance_partner'],
            'jurisdiction_id' => ['nullable', 'uuid', 'exists:regulatory_jurisdictions,id'],
            'risk_classification' => ['nullable', 'in:unassessed,low,medium,high'],
            'owner_id' => ['nullable', 'uuid', 'exists:users,id'],
        ]);
        $partner = DB::transaction(function () use ($data, $request): object {
            $id = (string) Str::uuid();
            DB::table('financial_partners')->insert($data + ['id' => $id, 'onboarding_status' => 'prospective', 'contract_status' => 'not_reviewed', 'api_readiness' => 'not_configured', 'risk_classification' => $data['risk_classification'] ?? 'unassessed', 'capabilities' => json_encode([]), 'sandbox_only' => true, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('partner_due_diligence')->insert(['id' => (string) Str::uuid(), 'partner_id' => $id, 'status' => 'not_started', 'checklist' => json_encode(['security' => 'pending', 'operational' => 'pending', 'financial' => 'pending', 'contract' => 'pending']), 'created_at' => now(), 'updated_at' => now()]);
            app(PlatformAuditLogger::class)->record('regulatory.partner.created', 'financial_partners', $id, $request, [], ['onboarding_status' => 'prospective', 'sandbox_only' => true], 'recorded', null, ['sandbox_only' => true]);
            return DB::table('financial_partners')->where('id', $id)->first();
        });
        return response()->json(['data' => $partner, 'message' => 'Partner recorded for due diligence. No provider capability was activated.', 'sandbox_only' => true], 201);
    }

    public function partner(Request $request, string $id): JsonResponse { $this->assert($request, 'merchants.read'); return $this->ok($this->readiness->partner($id)); }
    public function partnerCapabilities(Request $request, string $id): JsonResponse { $this->assert($request, 'settings.read'); return $this->page($this->readiness->partnerCapabilities($id)); }
    public function safeguardingOverview(Request $request): JsonResponse { $this->assert($request, 'financial.read'); return $this->ok($this->readiness->safeguardingOverview()); }
    public function safeguardingReconciliations(Request $request): JsonResponse { $this->assert($request, 'reconciliation.read'); return $this->page($this->readiness->reconciliations()); }
    public function safeguardingExceptions(Request $request): JsonResponse { $this->assert($request, 'reconciliation.read'); return $this->page($this->readiness->exceptions()); }
    public function assessments(Request $request): JsonResponse { $this->assert($request, 'compliance.read'); return $this->page($this->readiness->assessments()); }
    public function evidence(Request $request): JsonResponse { $this->assert($request, 'compliance.read'); return $this->page($this->readiness->evidence()); }
    public function launchReadiness(Request $request): JsonResponse { $this->assert($request, 'dashboard.read'); return $this->ok($this->readiness->readiness()); }
    public function launchGates(Request $request): JsonResponse { $this->assert($request, 'compliance.read'); return $this->page($this->readiness->gates()); }
    public function pilots(Request $request): JsonResponse { $this->assert($request, 'dashboard.read'); return $this->page($this->readiness->pilots()); }

    public function createLaunchAssessment(Request $request, AdminApprovalService $approvals): JsonResponse
    {
        $this->assert($request, 'approvals.request');
        $data = $request->validate([
            'jurisdiction_id' => ['nullable', 'uuid', 'exists:regulatory_jurisdictions,id'],
            'allowed_currencies' => ['required', 'array', 'min:1'],
            'allowed_currencies.*' => ['string', Rule::in(['USD', 'EUR', 'GBP', 'SGD', 'THB', 'MMK'])],
            'allowed_capabilities' => ['required', 'array', 'min:1'],
            'allowed_capabilities.*' => ['string', Rule::in(['sandbox_wallet_checkout', 'sandbox_provider_checkout', 'sandbox_qr', 'sandbox_fx_quote', 'sandbox_transfer_simulation', 'read_only_reporting'])],
            'monitoring_requirements' => ['required', 'string', 'max:4000'],
            'exit_criteria' => ['required', 'string', 'max:4000'],
            'reason' => ['required', 'string', 'max:1500'],
        ]);
        $idempotencyKey = (string) $request->header('Idempotency-Key');
        if ($idempotencyKey === '') throw ValidationException::withMessages(['idempotency_key' => 'Idempotency-Key is required.']);
        $payloadFingerprint = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
        $existingApproval = DB::table('admin_approval_requests')->where('requested_by', $request->user()->id)->where('idempotency_key', $idempotencyKey)->where('action', 'regulatory.launch.assessment')->first();
        if ($existingApproval) {
            $existingMetadata = is_array($existingApproval->metadata) ? $existingApproval->metadata : json_decode((string) $existingApproval->metadata, true);
            if (($existingMetadata['payload_fingerprint'] ?? null) !== $payloadFingerprint) throw ValidationException::withMessages(['idempotency_key' => 'The idempotency key was already used with a different request.']);
            $existingPilot = PilotAuthorization::where('approval_request_id', $existingApproval->id)->first();
            if ($existingPilot) return response()->json(['data' => ['pilot' => $existingPilot, 'approval' => $existingApproval], 'message' => 'Launch assessment request replayed safely; no activation occurred.', 'sandbox_only' => true], 202);
        }
        $pilot = PilotAuthorization::create([
            'pilot_reference' => 'PILOT-'.strtoupper(Str::random(12)),
            'jurisdiction_id' => $data['jurisdiction_id'] ?? null,
            'status' => 'planning',
            'approved_customer_group' => ['scope' => 'explicit_allowlist_required'],
            'approved_merchant_group' => ['scope' => 'explicit_allowlist_required'],
            'allowed_currencies' => array_map('strtoupper', $data['allowed_currencies']),
            'allowed_capabilities' => $data['allowed_capabilities'],
            'risk_limits' => ['source' => 'qualified_compliance_and_legal_review_required'],
            'provider_dependencies' => ['production_approval' => 'missing'],
            'monitoring_requirements' => $data['monitoring_requirements'],
            'exit_criteria' => $data['exit_criteria'],
            'created_by' => $request->user()->id,
        ]);
        $approval = $approvals->request($request->user(), 'approvals.request', 'regulatory.launch.assessment', PilotAuthorization::class, $pilot->id, $data['reason'], $idempotencyKey, 2);
        $approval->update(['metadata' => ['sandbox_only' => true, 'payload_fingerprint' => $payloadFingerprint]]);
        $pilot->update(['approval_request_id' => $approval->id]);
        app(PlatformAuditLogger::class)->record('regulatory.launch.assessment.requested', PilotAuthorization::class, $pilot->id, $request, [], ['status' => 'planning', 'sandbox_only' => true], 'pending_approval', $approval->id, ['sandbox_only' => true]);
        return response()->json(['data' => ['pilot' => $pilot->refresh(), 'approval' => $approval], 'message' => 'Launch assessment recorded for independent review. It cannot enable live financial execution.', 'sandbox_only' => true], 202);
    }

    private function assert(Request $request, string $permission): void { $this->access->assert($request->user(), $permission); }
    private function ok(array $data): JsonResponse { return response()->json(['data' => $data, 'sandbox_only' => true]); }
    private function page($page): JsonResponse { return response()->json(['data' => $page->items(), 'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()], 'sandbox_only' => true]); }
}
