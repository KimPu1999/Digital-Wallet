<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\AdminApprovalRequestForm;
use App\Http\Requests\AdminComplianceDecisionRequest;
use App\Http\Requests\AdminDecisionRequest;
use App\Http\Requests\AdminRestrictionRequest;
use App\Http\Requests\AdminRiskCaseRequest;
use App\Http\Requests\PlatformSettingRequest;
use App\Http\Requests\FeatureFlagRequest;
use App\Http\Resources\AdminApprovalResource;
use App\Http\Resources\RiskCaseResource;
use App\Http\Resources\UserResource;
use App\Models\AdminApprovalRequest;
use App\Models\AdminSecurityEvent;
use App\Models\AuditLog;
use App\Models\FinancialAuditEvent;
use App\Models\KycCase;
use App\Models\Merchant;
use App\Models\PaymentIntent;
use App\Models\PaymentProvider;
use App\Models\PlatformAdminMembership;
use App\Models\PlatformAuditEvent;
use App\Models\PlatformFeatureFlag;
use App\Models\PlatformPermission;
use App\Models\PlatformRole;
use App\Models\PlatformSetting;
use App\Models\PlatformSettingHistory;
use App\Models\ReconciliationRun;
use App\Models\RiskCase;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Wallet;
use App\Services\AdminApprovalService;
use App\Services\AdminStepUpService;
use App\Services\PlatformAdminAccessService;
use App\Services\PlatformAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class PlatformAdminController
{
    public function overview(Request $request): JsonResponse
    {
        $from = $request->filled('from') ? now()->parse($request->query('from'))->startOfDay() : now()->subDays(30)->startOfDay();
        $to = $request->filled('to') ? now()->parse($request->query('to'))->endOfDay() : now()->endOfDay();
        $payments = PaymentIntent::whereBetween('created_at', [$from, $to])->get(['status', 'amount_minor', 'currency_code', 'operation']);
        $transfers = Transfer::whereBetween('created_at', [$from, $to])->get(['status', 'source_amount_minor', 'source_currency_code']);
        return response()->json(['data' => ['period' => ['from' => $from, 'to' => $to], 'users' => User::whereBetween('created_at', [$from, $to])->count(), 'total_users' => User::count(), 'merchants' => Merchant::count(), 'wallets' => Wallet::count(), 'payments' => ['total' => $payments->count(), 'succeeded' => $payments->where('status', 'succeeded')->count(), 'failed' => $payments->whereIn('status', ['failed', 'cancelled'])->count()], 'transfers' => ['total' => $transfers->count(), 'succeeded' => $transfers->where('status', 'succeeded')->count()], 'payment_volume_by_currency' => $payments->where('status', 'succeeded')->groupBy('currency_code')->map(fn ($rows) => (int) $rows->sum('amount_minor')), 'transfer_volume_by_currency' => $transfers->where('status', 'succeeded')->groupBy('source_currency_code')->map(fn ($rows) => (int) $rows->sum('source_amount_minor')), 'compliance_queue' => KycCase::whereIn('status', ['under_review', 'manual_review'])->count(), 'risk_cases' => RiskCase::whereIn('status', ['open', 'investigating'])->count(), 'reconciliation_exceptions' => ReconciliationRun::where('status', '!=', 'completed')->withCount('items')->count(), 'provider_health' => PaymentProvider::where('enabled', true)->get(['code', 'health_status', 'environment']), 'sandbox_only' => true], 'sandbox_only' => true]);
    }

    public function users(Request $request): JsonResponse
    {
        $query = User::query()->when($request->filled('search'), fn ($q) => $q->where(fn ($s) => $s->where('name', 'ilike', '%'.$request->query('search').'%')->orWhere('email', 'ilike', '%'.$request->query('search').'%')))->when($request->filled('status'), fn ($q) => $q->where('account_status', $request->query('status')))->latest();
        if (DB::connection()->getDriverName() === 'sqlite' && $request->filled('search')) $query = User::query()->where(fn ($q) => $q->where('name', 'like', '%'.$request->query('search').'%')->orWhere('email', 'like', '%'.$request->query('search').'%'))->when($request->filled('status'), fn ($q) => $q->where('account_status', $request->query('status')))->latest();
        $page = $query->paginate(10);
        return response()->json(['data' => UserResource::collection($page->items()), 'meta' => $this->meta($page), 'sandbox_only' => true]);
    }

    public function user(Request $request, User $user): JsonResponse
    {
        $audits = PlatformAuditEvent::where('resource_type', User::class)->where('resource_id', $user->id)->latest('created_at')->limit(20)->get();
        return response()->json(['data' => ['user' => new UserResource($user), 'mfa_enabled' => app(\App\Services\MfaService::class)->enabled($user), 'sessions' => DB::table('active_sessions')->where('user_id', $user->id)->select(['id', 'ip_address', 'last_seen_at', 'created_at'])->latest('last_seen_at')->get(), 'login_activity' => DB::table('login_activities')->where('user_id', $user->id)->select(['event', 'ip_address', 'occurred_at'])->latest('occurred_at')->limit(20)->get(), 'audit_history' => $audits], 'sandbox_only' => true]);
    }

    public function restrictUser(AdminRestrictionRequest $request, User $user): JsonResponse
    {
        app(PlatformAdminAccessService::class)->assertSensitive($request->user(), 'users.restrict', $request->string('challenge_id')->toString(), $request->string('challenge_code')->toString());
        $approval = AdminApprovalRequest::whereKey($request->string('approval_request_id')->toString())->where('status', 'approved')->firstOrFail();
        abort_unless($approval->action === 'user.restrict' && (string) $approval->resource_id === (string) $user->id, 422, 'Approval does not match this user restriction.');
        $previous = ['account_status' => $user->account_status, 'is_active' => $user->is_active];
        $user->forceFill(['account_status' => $request->string('account_status')->toString(), 'is_active' => !in_array($request->string('account_status')->toString(), ['suspended', 'closed'], true)])->save();
        app(PlatformAuditLogger::class)->record('user.restriction.changed', User::class, $user->id, $request, $previous, ['account_status' => $user->account_status, 'is_active' => $user->is_active], 'approved', $approval->id, ['reason' => $request->string('reason')->toString(), 'sandbox_only' => true]);
        return response()->json(['data' => new UserResource($user->refresh()), 'sandbox_only' => true]);
    }

    public function merchants(Request $request): JsonResponse
    {
        $page = Merchant::query()->when($request->filled('search'), fn ($q) => $q->where(fn ($s) => $s->where('display_name', 'like', '%'.$request->query('search').'%')->orWhere('legal_name', 'like', '%'.$request->query('search').'%')))->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))->withCount('paymentIntents')->latest()->paginate(10);
        return response()->json(['data' => $page->items(), 'meta' => $this->meta($page), 'sandbox_only' => true]);
    }

    public function merchant(Request $request, Merchant $merchant): JsonResponse
    {
        $verification = DB::table('merchant_verifications')->where('merchant_id', $merchant->id)->latest('created_at')->first();
        return response()->json(['data' => ['merchant' => $merchant, 'kyb' => $verification, 'memberships' => DB::table('merchant_memberships')->where('merchant_id', $merchant->id)->join('users', 'users.id', '=', 'merchant_memberships.user_id')->select(['merchant_memberships.role', 'users.id', 'users.name', 'users.email'])->get(), 'payment_count' => $merchant->paymentIntents()->count(), 'settlement_count' => $merchant->settlements()->count()], 'sandbox_only' => true]);
    }

    public function compliance(Request $request): JsonResponse
    {
        $page = KycCase::with('user')->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))->latest()->paginate(10);
        return response()->json(['data' => $page->items(), 'meta' => $this->meta($page), 'sandbox_only' => true]);
    }

    public function complianceCase(Request $request, KycCase $case): JsonResponse
    {
        return response()->json(['data' => ['case' => $case->load('user'), 'documents' => $case->documents()->select(['id', 'document_type', 'mime_type', 'size_bytes', 'scan_status', 'review_status', 'uploaded_at'])->get(), 'sandbox_only' => true], 'sandbox_only' => true]);
    }

    public function complianceDecision(AdminComplianceDecisionRequest $request, KycCase $case): JsonResponse
    {
        app(PlatformAdminAccessService::class)->assertSensitive($request->user(), 'compliance.decide', $request->string('challenge_id')->toString(), $request->string('challenge_code')->toString());
        $previous = ['status' => $case->status];
        $case->update(['status' => $request->string('status')->toString(), 'reviewed_at' => now()]);
        DB::table('kyc_reviews')->insert(['id' => (string) \Illuminate\Support\Str::uuid(), 'kyc_case_id' => $case->id, 'reviewer_id' => $request->user()->id, 'decision' => $request->string('status')->toString(), 'notes' => $request->string('reason')->toString(), 'created_at' => now(), 'updated_at' => now()]);
        app(PlatformAuditLogger::class)->record('compliance.kyc.decision', KycCase::class, $case->id, $request, $previous, ['status' => $case->status], $case->status, null, ['reason' => $request->string('reason')->toString(), 'sandbox_only' => true]);
        return response()->json(['data' => $case->refresh(), 'message' => 'Manual compliance decision recorded. Mock screening does not auto-approve.', 'sandbox_only' => true]);
    }

    public function financial(Request $request): JsonResponse
    {
        $from = $request->filled('from') ? now()->parse($request->query('from'))->startOfDay() : now()->subDays(30)->startOfDay();
        $to = $request->filled('to') ? now()->parse($request->query('to'))->endOfDay() : now()->endOfDay();
        $payments = PaymentIntent::whereBetween('created_at', [$from, $to])->get(['id', 'merchant_id', 'operation', 'status', 'amount_minor', 'currency_code', 'settlement_reference', 'created_at']);
        return response()->json(['data' => ['payments' => $payments->groupBy('currency_code')->map(fn ($rows) => ['count' => $rows->count(), 'succeeded' => $rows->where('status', 'succeeded')->count(), 'amount_minor' => (int) $rows->where('status', 'succeeded')->sum('amount_minor')]), 'ledger_transactions' => DB::table('ledger_transactions')->whereBetween('created_at', [$from, $to])->count(), 'transfers' => Transfer::whereBetween('created_at', [$from, $to])->count(), 'reconciliation_runs' => ReconciliationRun::latest('started_at')->limit(10)->get(), 'sandbox_only' => true], 'sandbox_only' => true]);
    }

    public function riskCases(Request $request): JsonResponse
    {
        $page = RiskCase::with('events')->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))->when($request->filled('severity'), fn ($q) => $q->where('severity', $request->query('severity')))->latest()->paginate(10);
        return response()->json(['data' => RiskCaseResource::collection($page->items()), 'meta' => $this->meta($page), 'sandbox_only' => true]);
    }

    public function createRiskCase(AdminRiskCaseRequest $request): JsonResponse
    {
        $case = RiskCase::create(['subject_type' => $request->string('subject_type')->toString(), 'subject_id' => $request->string('subject_id')->toString(), 'opened_by' => $request->user()->id, 'status' => 'open', 'severity' => $request->string('severity')->toString(), 'reason' => $request->string('reason')->toString(), 'metadata' => ['sandbox_only' => true]]);
        $case->events()->create(['actor_id' => $request->user()->id, 'event_type' => 'case_opened', 'notes' => $case->reason, 'metadata' => ['sandbox_only' => true], 'created_at' => now()]);
        app(PlatformAuditLogger::class)->record('risk.case.opened', RiskCase::class, $case->id, $request, [], ['status' => 'open', 'severity' => $case->severity], 'recorded', null, ['sandbox_only' => true]);
        return response()->json(['data' => new RiskCaseResource($case->load('events')), 'sandbox_only' => true], 201);
    }

    public function approvals(Request $request): JsonResponse
    {
        $page = AdminApprovalRequest::with('decisions')->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))->latest()->paginate(10);
        return response()->json(['data' => AdminApprovalResource::collection($page->items()), 'meta' => $this->meta($page), 'sandbox_only' => true]);
    }

    public function createApproval(AdminApprovalRequestForm $request, AdminApprovalService $service): JsonResponse
    {
        $approval = $service->request($request->user(), 'approvals.request', $request->string('action')->toString(), $request->input('resource_type'), $request->input('resource_id'), $request->string('reason')->toString(), (string) $request->header('Idempotency-Key'), (int) $request->input('required_decisions', 1));
        return response()->json(['data' => new AdminApprovalResource($approval), 'sandbox_only' => true], 201);
    }

    public function decideApproval(AdminDecisionRequest $request, AdminApprovalRequest $approval, AdminApprovalService $service): JsonResponse
    {
        return response()->json(['data' => new AdminApprovalResource($service->decide($request->user(), 'approvals.decide', $approval, $request->string('decision')->toString(), $request->string('reason')->toString(), $request->string('challenge_id')->toString(), $request->string('challenge_code')->toString())), 'sandbox_only' => true]);
    }

    public function audit(Request $request): JsonResponse
    {
        $platform = PlatformAuditEvent::latest('created_at')->paginate(10);
        return response()->json(['data' => ['platform' => $platform->items(), 'security' => AdminSecurityEvent::latest('created_at')->limit(20)->get(), 'financial' => FinancialAuditEvent::latest('created_at')->limit(20)->get(), 'legacy' => AuditLog::latest()->limit(20)->get()], 'meta' => $this->meta($platform), 'sandbox_only' => true]);
    }

    public function settings(Request $request): JsonResponse
    {
        return response()->json(['data' => ['settings' => PlatformSetting::orderBy('setting_key')->get(), 'feature_flags' => PlatformFeatureFlag::orderBy('flag_key')->get(), 'roles' => PlatformRole::with('permissions')->get(), 'permissions' => PlatformPermission::orderBy('name')->get()], 'sandbox_only' => true]);
    }

    public function updateSetting(PlatformSettingRequest $request, string $key): JsonResponse
    {
        $existing = PlatformSetting::where('setting_key', $key)->first();
        $critical = $existing?->critical ?? str_starts_with($key, 'financial.') || str_starts_with($key, 'provider.');
        app(PlatformAdminAccessService::class)->assertSensitive($request->user(), 'settings.manage', $request->string('challenge_id')->toString(), $request->string('challenge_code')->toString());
        $approval = $request->filled('approval_request_id') ? AdminApprovalRequest::whereKey($request->string('approval_request_id')->toString())->where('status', 'approved')->first() : null;
        if ($critical && (!$approval || $approval->action !== 'settings.change' || $approval->resource_id !== null)) throw ValidationException::withMessages(['approval_request_id' => 'An approved settings.change request is required for critical settings.']);
        $setting = $existing ?: new PlatformSetting(['setting_key' => $key, 'version' => 0, 'critical' => $critical]);
        $previous = $setting->exists ? $setting->value : [];
        $setting->fill(['value' => $request->input('value'), 'version' => ((int) $setting->version) + 1, 'critical' => $critical])->save();
        PlatformSettingHistory::create(['setting_key' => $key, 'version' => $setting->version, 'value' => $setting->value, 'changed_by' => $request->user()->id, 'approval_request_id' => $approval?->id, 'created_at' => now()]);
        app(PlatformAuditLogger::class)->record('platform.setting.changed', PlatformSetting::class, $setting->id, $request, ['value' => $previous], ['value' => $setting->value, 'version' => $setting->version], 'approved', $approval?->id, ['setting_key' => $key, 'sandbox_only' => true]);
        return response()->json(['data' => $setting->refresh(), 'sandbox_only' => true]);
    }

    public function updateFeatureFlag(FeatureFlagRequest $request, string $flag): JsonResponse
    {
        app(PlatformAdminAccessService::class)->assertSensitive($request->user(), 'settings.manage', $request->string('challenge_id')->toString(), $request->string('challenge_code')->toString());
        $approval = $request->filled('approval_request_id') ? AdminApprovalRequest::whereKey($request->string('approval_request_id')->toString())->where('status', 'approved')->first() : null;
        if (!$approval || $approval->action !== 'feature.change') throw ValidationException::withMessages(['approval_request_id' => 'An approved feature.change request is required.']);
        $record = PlatformFeatureFlag::where('flag_key', $flag)->firstOrFail();
        $previous = ['enabled' => $record->enabled];
        $record->update(['enabled' => (bool) $request->boolean('enabled')]);
        app(PlatformAuditLogger::class)->record('platform.feature_flag.changed', PlatformFeatureFlag::class, $record->id, $request, $previous, ['enabled' => $record->enabled], 'approved', $approval->id, ['flag_key' => $flag, 'sandbox_only' => true]);
        return response()->json(['data' => $record->refresh(), 'sandbox_only' => true]);
    }

    public function health(Request $request): JsonResponse
    {
        $database = 'ok';
        try { DB::select('select 1'); } catch (\Throwable) { $database = 'unavailable'; }
        return response()->json(['data' => ['api' => 'ok', 'database' => $database, 'queue_connection' => config('queue.default'), 'scheduler' => 'not_probeable_from_http', 'webhook_backlog' => Schema::hasTable('merchant_webhook_deliveries') ? DB::table('merchant_webhook_deliveries')->whereIn('status', ['queued', 'retrying'])->count() : 0, 'failed_jobs' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0, 'providers' => PaymentProvider::where('environment', 'sandbox')->get(['code', 'enabled', 'status', 'health_status']), 'sandbox_only' => true], 'sandbox_only' => true]);
    }

    public function stepUp(Request $request, AdminStepUpService $service): JsonResponse
    {
        return response()->json(['data' => $service->issue($request->user()), 'sandbox_only' => true]);
    }

    private function meta($page): array { return ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()]; }
}
