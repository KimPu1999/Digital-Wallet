<?php
namespace App\Http\Controllers\Api\V1;

use App\Domain\Compliance\ComplianceCaseService;
use App\Domain\Compliance\ComplianceReportingService;
use App\Http\Requests\ComplianceCaseEventRequest;
use App\Http\Requests\ComplianceCaseRequest;
use App\Http\Requests\RegulatoryReportDraftRequest;
use App\Models\AuthSecurityEvent;
use App\Models\CaseEvidence;
use App\Models\ComplianceCase;
use App\Models\CustomerRiskProfile;
use App\Models\DeviceRegistration;
use App\Models\PaymentIntent;
use App\Models\RegulatoryReportDraft;
use App\Models\RiskAlert;
use App\Models\RiskRule;
use App\Models\RiskRuleVersion;
use App\Models\ScreeningMatch;
use App\Models\ScreeningRequest;
use App\Models\Transfer;
use App\Services\PlatformAdminAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PlatformSecurityController
{
    public function overview(): JsonResponse
    {
        return response()->json(['data' => ['open_alerts' => RiskAlert::whereIn('status', ['open', 'investigating'])->count(), 'high_risk_alerts' => RiskAlert::whereIn('severity', ['high', 'critical'])->whereIn('status', ['open', 'investigating'])->count(), 'pending_screening_reviews' => ScreeningMatch::where('status', 'potential_match')->count(), 'compliance_cases' => ComplianceCase::whereIn('status', ['open', 'assigned', 'investigating', 'escalated'])->count(), 'authentication_anomalies' => AuthSecurityEvent::whereIn('severity', ['high', 'critical'])->where('occurred_at', '>=', now()->subDays(30))->count(), 'rule_evaluation_failures' => DB::table('risk_evaluations')->whereNotNull('failure_reason')->where('created_at', '>=', now()->subDays(30))->count(), 'screening_provider_health' => ScreeningRequest::query()->select('provider', DB::raw('count(*) as total'), DB::raw("sum(case when status = 'failed' then 1 else 0 end) as failures"))->groupBy('provider')->get(), 'sandbox_only' => true], 'sandbox_only' => true]);
    }

    public function securityEvents(Request $request): JsonResponse { return $this->page(AuthSecurityEvent::query()->when($request->filled('severity'), fn ($q) => $q->where('severity', $request->query('severity')))->when($request->filled('event_type'), fn ($q) => $q->where('event_type', $request->query('event_type')))->latest('occurred_at')->paginate(10)); }
    public function amlTransactions(Request $request): JsonResponse
    {
        $payments = PaymentIntent::query()->select(['id','user_id','merchant_id','operation','status','currency_code','amount_minor','created_at'])->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))->latest()->paginate(10);
        return response()->json(['data' => ['payments' => $payments->items(), 'transfers' => Transfer::latest()->limit(10)->get(['id','user_id','status','source_currency_code','source_amount_minor','created_at'])], 'meta' => $this->meta($payments), 'sandbox_only' => true]);
    }
    public function rules(Request $request): JsonResponse { return $this->page(RiskRule::with(['versions' => fn ($q) => $q->latest('version')])->when($request->filled('category'), fn ($q) => $q->where('category', $request->query('category')))->orderBy('rule_key')->paginate(10)); }
    public function alerts(Request $request): JsonResponse { return $this->page(RiskAlert::with('evaluation')->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))->when($request->filled('severity'), fn ($q) => $q->where('severity', $request->query('severity')))->latest()->paginate(10)); }
    public function alert(RiskAlert $alert): JsonResponse { return response()->json(['data' => $alert->load('evaluation.ruleVersion'), 'sandbox_only' => true]); }
    public function screeningRequests(Request $request): JsonResponse { return $this->page(ScreeningRequest::with('matches')->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))->latest()->paginate(10)); }
    public function screeningMatches(Request $request): JsonResponse { return $this->page(ScreeningMatch::with('request')->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))->latest()->paginate(10)); }
    public function screeningMatch(ScreeningMatch $match): JsonResponse { return response()->json(['data' => $match->load('request'), 'sandbox_only' => true]); }
    public function fraudOverview(): JsonResponse { return response()->json(['data' => ['open_alerts' => RiskAlert::where('category', 'fraud')->whereIn('status', ['open','investigating'])->count(), 'new_devices' => AuthSecurityEvent::where('event_type', 'new_device')->where('occurred_at', '>=', now()->subDays(30))->count(), 'failed_auth_events' => AuthSecurityEvent::where('event_type', 'failed_login')->where('occurred_at', '>=', now()->subDays(30))->count(), 'sandbox_only' => true], 'sandbox_only' => true]); }
    public function fraudAlerts(Request $request): JsonResponse { return $this->page(RiskAlert::where('category', 'fraud')->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))->latest()->paginate(10)); }
    public function fraudRules(Request $request): JsonResponse { return $this->page(RiskRule::where('category', 'fraud')->with('versions')->paginate(10)); }
    public function devices(Request $request): JsonResponse { return $this->page(DeviceRegistration::with('user:id,name,email')->latest('last_seen_at')->paginate(10)); }
    public function sessions(Request $request): JsonResponse { return $this->page(DB::table('active_sessions')->join('users', 'users.id', '=', 'active_sessions.user_id')->select(['active_sessions.id', 'active_sessions.user_id', 'users.name', 'users.email', 'active_sessions.ip_address', 'active_sessions.last_seen_at', 'active_sessions.created_at'])->latest('active_sessions.last_seen_at')->paginate(10)); }
    public function authEvents(Request $request): JsonResponse { return $this->page(AuthSecurityEvent::with('user:id,name,email')->latest('occurred_at')->paginate(10)); }
    public function cases(Request $request): JsonResponse { return $this->page(ComplianceCase::with(['events','evidence'])->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->query('priority')))->latest()->paginate(10)); }
    public function createCase(ComplianceCaseRequest $request, ComplianceCaseService $service): JsonResponse { return response()->json(['data' => $service->create($request->validated(), $request), 'sandbox_only' => true], 201); }
    public function case(ComplianceCase $case): JsonResponse { return response()->json(['data' => $case->load(['events','evidence']), 'sandbox_only' => true]); }
    public function caseEvent(ComplianceCaseEventRequest $request, ComplianceCase $case, ComplianceCaseService $service, PlatformAdminAccessService $access): JsonResponse { $access->assertSensitive($request->user(), 'compliance.decide', $request->string('challenge_id')->toString(), $request->string('challenge_code')->toString()); return response()->json(['data' => $service->transition($case, $request->string('status')->toString(), $request->string('notes')->toString(), $request), 'sandbox_only' => true]); }
    public function reports(Request $request): JsonResponse { return $this->page(RegulatoryReportDraft::query()->latest()->paginate(10)); }
    public function createReport(RegulatoryReportDraftRequest $request, ComplianceReportingService $service): JsonResponse { return response()->json(['data' => $service->createDraft($request->validated(), $request), 'message' => 'Draft prepared for authorized review; no regulatory report was submitted.', 'sandbox_only' => true], 201); }
    public function riskProfiles(Request $request): JsonResponse { return $this->page(CustomerRiskProfile::with('user:id,name,email')->when($request->filled('classification'), fn ($q) => $q->where('classification', $request->query('classification')))->latest()->paginate(10)); }
    public function evidence(ComplianceCase $case, CaseEvidence $evidence): JsonResponse { abort_unless((string) $evidence->compliance_case_id === (string) $case->id, 404); return response()->json(['data' => $evidence, 'message' => 'Restricted evidence metadata only. Raw files are not exposed through this endpoint.', 'sandbox_only' => true], 200); }
    private function page($page): JsonResponse { return response()->json(['data' => $page->items(), 'meta' => $this->meta($page), 'sandbox_only' => true]); }
    private function meta($page): array { return ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()]; }
}
