<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Enterprise\EnterpriseOperationsService;
use App\Models\AdminApprovalRequest;
use App\Models\OperationalIncident;
use App\Models\PrivacyRequest;
use App\Models\ProductFeedback;
use App\Models\ReleaseCandidate;
use App\Services\AdminApprovalService;
use App\Services\PlatformAdminAccessService;
use App\Services\PlatformAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PlatformEnterpriseController
{
    public function __construct(private EnterpriseOperationsService $enterprise, private PlatformAdminAccessService $access) {}

    public function overview(Request $request): JsonResponse { $this->assert($request, 'dashboard.read'); return response()->json(['data' => $this->enterprise->overview(), 'sandbox_only' => true]); }
    public function health(Request $request): JsonResponse { $this->assert($request, 'system.health'); return response()->json(['data' => $this->enterprise->health(), 'sandbox_only' => true]); }
    public function features(Request $request): JsonResponse { $this->assert($request, 'settings.read'); return $this->page($this->enterprise->featureFlags()); }
    public function featureHistory(Request $request): JsonResponse { $this->assert($request, 'audit.read'); return $this->page($this->enterprise->featureHistory()); }
    public function policies(Request $request): JsonResponse { $this->assert($request, 'settings.read'); return $this->page($this->enterprise->policies()); }
    public function accessReviews(Request $request): JsonResponse { $this->assert($request, 'audit.read'); return $this->page($this->enterprise->accessReviews()); }
    public function createAccessReview(Request $request): JsonResponse { $this->assert($request, 'settings.manage'); $data = $request->validate(['name' => ['required', 'string', 'max:160'], 'scope' => ['required', 'string', 'max:60'], 'due_at' => ['nullable', 'date']]); $id = (string) Str::uuid(); DB::table('access_review_campaigns')->insert($data + ['id' => $id, 'status' => 'open', 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]); return response()->json(['data' => DB::table('access_review_campaigns')->where('id', $id)->first(), 'sandbox_only' => true], 201); }
    public function approvals(Request $request): JsonResponse { $this->assert($request, 'approvals.request'); return $this->page(AdminApprovalRequest::latest()->paginate(10)); }
    public function customers(Request $request): JsonResponse { $this->assert($request, 'users.read'); return $this->page($this->enterprise->lifecycle('customers')); }
    public function merchants(Request $request): JsonResponse { $this->assert($request, 'merchants.read'); return $this->page($this->enterprise->lifecycle('merchants')); }
    public function lifecycleChange(Request $request, AdminApprovalService $approvals): JsonResponse { $this->assert($request, 'approvals.request'); $data = $request->validate(['subject_type' => ['required', 'in:user,merchant'], 'subject_id' => ['required', 'uuid'], 'to_status' => ['required', 'string', 'max:32'], 'reason' => ['required', 'string', 'max:1000']]); $approval = $approvals->request($request->user(), 'approvals.request', 'lifecycle.change', $data['subject_type'], $data['subject_id'], $data['reason'], (string) $request->header('Idempotency-Key'), 2); return response()->json(['data' => $approval, 'message' => 'Lifecycle change submitted for independent approval; no status was changed.', 'sandbox_only' => true], 202); }
    public function privacy(Request $request): JsonResponse { $this->assert($request, 'compliance.read'); return $this->page($this->enterprise->privacyRequests()); }
    public function retention(Request $request): JsonResponse { $this->assert($request, 'settings.read'); return $this->page($this->enterprise->retentionPolicies()); }
    public function incidents(Request $request): JsonResponse { $this->assert($request, 'support.read'); return $this->page($this->enterprise->incidents()); }
    public function releases(Request $request): JsonResponse { $this->assert($request, 'audit.read'); return $this->page($this->enterprise->releases()); }
    public function analytics(Request $request): JsonResponse { $this->assert($request, 'dashboard.read'); return response()->json(['data' => ['generated_at' => now()->toIso8601String(), 'customers_by_status' => DB::table('users')->select('account_status', DB::raw('count(*) as total'))->groupBy('account_status')->get(), 'wallets_by_status' => DB::table('wallets')->select('status', DB::raw('count(*) as total'))->groupBy('status')->get()], 'sandbox_only' => true]); }
    public function funnels(Request $request): JsonResponse { $this->assert($request, 'dashboard.read'); return response()->json(['data' => ['generated_at' => now()->toIso8601String(), 'customer_onboarding' => DB::table('users')->select('account_status as stage', DB::raw('count(*) as total'))->groupBy('account_status')->get(), 'merchant_onboarding' => DB::table('merchants')->select('status as stage', DB::raw('count(*) as total'))->groupBy('status')->get()], 'sandbox_only' => true]); }
    public function feedback(Request $request): JsonResponse { $this->assert($request, 'support.read'); return $this->page($this->enterprise->feedback()); }

    public function requestFeatureChange(Request $request, AdminApprovalService $approvals): JsonResponse
    {
        $this->assert($request, 'settings.manage');
        $data = $request->validate(['flag_key' => ['required', 'string', 'max:120'], 'enabled' => ['required', 'boolean'], 'reason' => ['required', 'string', 'max:1000']]);
        $approval = $approvals->request($request->user(), 'approvals.request', 'enterprise.feature.change', 'platform_feature_flags', null, $data['reason'], (string) $request->header('Idempotency-Key'), 2);
        return response()->json(['data' => $approval, 'message' => 'Feature change submitted for maker-checker approval; no flag was changed.', 'sandbox_only' => true], 202);
    }

    public function createIncident(Request $request): JsonResponse
    {
        $this->assert($request, 'support.manage');
        $data = $request->validate(['category' => ['required', 'string', 'max:40'], 'severity' => ['required', 'in:low,medium,high,critical'], 'title' => ['required', 'string', 'max:180'], 'summary' => ['required', 'string', 'max:5000']]);
        $incident = OperationalIncident::create($data + ['incident_reference' => 'INC-'.strtoupper(Str::random(12)), 'status' => 'open', 'owner_id' => $request->user()->id, 'detected_at' => now(), 'metadata' => ['sandbox_only' => true]]);
        app(PlatformAuditLogger::class)->record('enterprise.incident.created', OperationalIncident::class, $incident->id, $request, [], ['status' => 'open', 'severity' => $incident->severity], 'recorded', null, ['sandbox_only' => true]);
        return response()->json(['data' => $incident, 'sandbox_only' => true], 201);
    }

    public function incident(Request $request, string $id): JsonResponse { $this->assert($request, 'support.read'); $incident = OperationalIncident::findOrFail($id); return response()->json(['data' => ['incident' => $incident, 'events' => DB::table('incident_events')->where('incident_id', $id)->latest('created_at')->get(), 'action_items' => DB::table('incident_action_items')->where('incident_id', $id)->latest('created_at')->get()], 'sandbox_only' => true]); }
    public function incidentEvent(Request $request, string $id): JsonResponse { $this->assert($request, 'support.manage'); OperationalIncident::findOrFail($id); $data = $request->validate(['event_type' => ['required', 'string', 'max:60'], 'summary' => ['required', 'string', 'max:3000']]); $eventId = (string) Str::uuid(); DB::table('incident_events')->insert($data + ['id' => $eventId, 'incident_id' => $id, 'actor_id' => $request->user()->id, 'created_at' => now()]); return response()->json(['data' => DB::table('incident_events')->where('id', $eventId)->first(), 'sandbox_only' => true], 201); }

    public function createPrivacyRequest(Request $request): JsonResponse
    {
        $this->assert($request, 'compliance.read');
        $data = $request->validate(['subject_user_id' => ['required', 'uuid'], 'request_type' => ['required', 'in:access,correction,restriction,deletion,export'], 'scope_summary' => ['nullable', 'string', 'max:1000']]);
        $requestRecord = PrivacyRequest::create($data + ['status' => 'submitted', 'verification_status' => 'pending']);
        return response()->json(['data' => $requestRecord, 'message' => 'Privacy request is pending identity verification and retention review.', 'sandbox_only' => true], 201);
    }

    public function createRelease(Request $request, AdminApprovalService $approvals): JsonResponse
    {
        $this->assert($request, 'approvals.request');
        $data = $request->validate(['product' => ['required', 'in:frontend,backend,mobile,infrastructure'], 'version' => ['required', 'string', 'max:60'], 'environment' => ['required', 'in:development,staging,sandbox-beta,production'], 'rollback_plan' => ['required', 'string', 'max:3000']]);
        $release = ReleaseCandidate::create($data + ['release_reference' => 'REL-'.strtoupper(Str::random(12)), 'status' => 'proposed', 'created_by' => $request->user()->id, 'test_evidence' => ['sandbox_only' => true]]);
        $approval = $approvals->request($request->user(), 'approvals.request', 'release.approve', ReleaseCandidate::class, $release->id, 'Release candidate approval: '.$release->release_reference, (string) $request->header('Idempotency-Key'), 2);
        return response()->json(['data' => ['release' => $release, 'approval' => $approval], 'sandbox_only' => true], 201);
    }

    public function release(Request $request, string $id): JsonResponse { $this->assert($request, 'audit.read'); return response()->json(['data' => ['release' => ReleaseCandidate::findOrFail($id), 'approvals' => DB::table('release_approvals')->where('release_candidate_id', $id)->latest('decided_at')->get()], 'sandbox_only' => true]); }
    public function releaseApproval(Request $request, string $id, AdminApprovalService $approvals): JsonResponse { $release = ReleaseCandidate::findOrFail($id); $approval = AdminApprovalRequest::where('action', 'release.approve')->where('resource_id', $release->id)->where('status', 'pending')->latest()->firstOrFail(); $data = $request->validate(['decision' => ['required', 'in:approved,rejected'], 'reason' => ['required', 'string', 'max:1000'], 'challenge_id' => ['required', 'string'], 'challenge_code' => ['required', 'string']]); $result = $approvals->decide($request->user(), 'approvals.decide', $approval, $data['decision'], $data['reason'], $data['challenge_id'], $data['challenge_code']); DB::table('release_approvals')->insert(['id' => (string) Str::uuid(), 'release_candidate_id' => $release->id, 'approved_by' => $request->user()->id, 'decision' => $data['decision'], 'reason' => $data['reason'], 'decided_at' => now()]); if ($result->status === 'approved') $release->update(['status' => 'approved']); return response()->json(['data' => $result, 'sandbox_only' => true]); }

    public function createFeedback(Request $request): JsonResponse
    {
        $this->assert($request, 'support.manage');
        $data = $request->validate(['source' => ['required', 'string', 'max:32'], 'category' => ['required', 'string', 'max:40'], 'priority' => ['required', 'in:low,normal,high'], 'redacted_summary' => ['required', 'string', 'max:3000'], 'product_area' => ['nullable', 'string', 'max:80']]);
        return response()->json(['data' => ProductFeedback::create($data + ['status' => 'new']), 'sandbox_only' => true], 201);
    }

    private function assert(Request $request, string $permission): void { $this->access->assert($request->user(), $permission); }
    private function page($page): JsonResponse { return response()->json(['data' => $page->items(), 'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()], 'sandbox_only' => true]); }
}
