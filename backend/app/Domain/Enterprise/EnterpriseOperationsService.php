<?php

namespace App\Domain\Enterprise;

use App\Models\PlatformFeatureFlag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class EnterpriseOperationsService
{
    public function overview(): array
    {
        return [
            'sandbox_only' => true,
            'generated_at' => now()->toIso8601String(),
            'customers' => DB::table('users')->where('user_type', 'customer')->count(),
            'merchants' => DB::table('merchants')->count(),
            'wallets' => DB::table('wallets')->count(),
            'support_backlog' => $this->countIfExists('support_tickets', ['open', 'assigned', 'in_progress']),
            'risk_alerts' => $this->countIfExists('risk_alerts', ['open', 'review']),
            'pending_approvals' => DB::table('admin_approval_requests')->where('status', 'pending')->count(),
            'active_incidents' => DB::table('operational_incidents')->whereIn('status', ['open', 'investigating', 'mitigating'])->count(),
            'feature_flags' => PlatformFeatureFlag::orderBy('flag_key')->get(),
        ];
    }

    public function health(): array
    {
        $database = 'ok';
        try { DB::select('select 1'); } catch (\Throwable) { $database = 'unavailable'; }
        return ['api' => 'ok', 'database' => $database, 'queue' => config('queue.default'), 'financial_execution_enabled' => (bool) config('app.financial_execution_enabled', false), 'observed_at' => now()->toIso8601String(), 'sandbox_only' => true];
    }

    public function featureFlags() { return PlatformFeatureFlag::orderBy('flag_key')->paginate(10); }
    public function featureHistory() { return DB::table('feature_rollout_versions')->latest('created_at')->paginate(10); }
    public function policies() { return DB::table('enterprise_policies')->latest('updated_at')->paginate(10); }
    public function accessReviews() { return DB::table('access_review_campaigns')->latest('created_at')->paginate(10); }
    public function privacyRequests() { return DB::table('privacy_requests')->latest('created_at')->paginate(10); }
    public function retentionPolicies() { return DB::table('data_retention_policies')->latest('updated_at')->paginate(10); }
    public function incidents() { return DB::table('operational_incidents')->latest('created_at')->paginate(10); }
    public function releases() { return DB::table('release_candidates')->latest('created_at')->paginate(10); }
    public function feedback() { return DB::table('product_feedback')->latest('created_at')->paginate(10); }

    public function lifecycle(string $type)
    {
        if ($type === 'merchants') return DB::table('merchants')->select(['id', 'legal_name', 'display_name', 'status', 'created_at', 'updated_at'])->latest('created_at')->paginate(10);
        return DB::table('users')->select(['id', 'name', 'email', 'country_code', 'account_status', 'user_type', 'email_verified_at', 'is_active', 'created_at', 'updated_at'])->latest('created_at')->paginate(10);
    }

    public function featureEnabled(string $flagKey, string $subjectKey, string $environment = 'sandbox'): bool
    {
        $flag = PlatformFeatureFlag::where('flag_key', $flagKey)->where('environment', $environment)->first();
        if (!$flag || !$flag->enabled || ($flag->scheduled_at && now()->lt($flag->scheduled_at)) || ($flag->expires_at && now()->gte($flag->expires_at))) return false;
        $rollout = (int) ($flag->rollout_percentage ?? 0);
        if ($rollout >= 100) return true;
        if ($rollout <= 0) return false;
        return hexdec(substr(hash('sha256', $flagKey.':'.$subjectKey), 0, 8)) % 100 < $rollout;
    }

    private function countIfExists(string $table, array $statuses): int
    {
        return Schema::hasTable($table) ? DB::table($table)->whereIn('status', $statuses)->count() : 0;
    }
}
