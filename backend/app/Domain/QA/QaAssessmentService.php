<?php
namespace App\Domain\QA;
use App\Models\MaintenanceItem;
use App\Models\PlatformFeatureFlag;
use App\Models\QaModuleInventory;
use App\Models\QaFinding;
use App\Models\QaPerformanceRun;
use App\Models\QaRecoveryDrill;
use App\Models\QaReleaseGate;
use App\Models\QaTestRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
final class QaAssessmentService {
    public function overview(): array {
        $latest = QaTestRun::query()->latest('finished_at')->get()->groupBy('category')->map(fn ($runs) => $runs->first())->values();
        return ['sandbox_only'=>true, 'test_runs'=>['total'=>QaTestRun::count(),'latest'=>$latest], 'findings'=>['open'=>QaFinding::whereIn('status',['open','in_progress'])->count(),'critical'=>QaFinding::where('severity','critical')->whereIn('status',['open','in_progress'])->count()], 'performance_runs'=>QaPerformanceRun::latest()->limit(10)->get(), 'recovery_drills'=>QaRecoveryDrill::latest()->limit(10)->get(), 'release_gates'=>$this->gates(), 'module_coverage'=>$this->moduleCoverage()->items(), 'feature_inventory'=>$this->featureInventory(), 'maintenance_backlog'=>$this->maintenanceBacklog()->items(), 'financial_integrity'=>$this->financialIntegrity(), 'infrastructure'=>$this->infrastructureReadiness()];
    }

    public function moduleCoverage() { return Schema::hasTable('qa_module_inventory') ? QaModuleInventory::query()->orderBy('phase')->orderBy('name')->paginate(10) : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10, 1); }

    public function featureInventory(): array
    {
        if (!Schema::hasTable('platform_feature_flags')) return [];
        return PlatformFeatureFlag::query()->orderBy('flag_key')->get(['flag_key', 'enabled', 'environment', 'category', 'rollout_percentage', 'updated_at'])->all();
    }

    public function maintenanceBacklog() { return Schema::hasTable('maintenance_items') ? MaintenanceItem::query()->latest('priority')->latest('created_at')->paginate(10) : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10, 1); }

    public function financialIntegrity(): array
    {
        if (!Schema::hasTable('ledger_entries') || !Schema::hasTable('ledger_transactions')) return ['status' => 'blocked', 'posted_transactions' => 0, 'imbalances' => [], 'reconciliation_exceptions' => 0, 'reason' => 'Ledger evidence tables are unavailable.'];
        $rows = DB::table('ledger_entries as entries')->join('ledger_transactions as transactions', 'transactions.id', '=', 'entries.ledger_transaction_id')->where('transactions.status', 'posted')->select('entries.currency_code', 'entries.side', DB::raw('sum(entries.amount_minor) as total_minor'))->groupBy('entries.currency_code', 'entries.side')->get();
        $byCurrency = [];
        foreach ($rows as $row) $byCurrency[$row->currency_code][(string) $row->side] = (int) $row->total_minor;
        $imbalances = collect($byCurrency)->map(function (array $sides, string $currency): ?array { $debit = $sides['debit'] ?? 0; $credit = $sides['credit'] ?? 0; return $debit === $credit ? null : ['currency_code' => $currency, 'debit_minor' => $debit, 'credit_minor' => $credit, 'difference_minor' => $debit - $credit]; })->filter()->values()->all();
        $exceptions = Schema::hasTable('reconciliation_items') ? DB::table('reconciliation_items')->whereIn('status', ['exception', 'failed'])->count() : 0;
        $posted = DB::table('ledger_transactions')->where('status', 'posted')->count();
        return ['status' => $posted === 0 ? 'blocked' : ($imbalances || $exceptions > 0 ? 'blocked' : 'ready'), 'posted_transactions' => $posted, 'imbalances' => $imbalances, 'reconciliation_exceptions' => $exceptions, 'reason' => $posted === 0 ? 'No posted-ledger evidence has been recorded.' : ($imbalances || $exceptions > 0 ? 'Financial integrity exceptions require review.' : 'Posted entries reconcile by currency.')];
    }

    public function infrastructureReadiness(): array
    {
        $checks = ['docker_compose' => file_exists(base_path('../docker-compose.yml')), 'backend_container' => file_exists(base_path('Dockerfile')), 'frontend_container' => file_exists(base_path('../frontend/Dockerfile')), 'ci_workflow' => file_exists(base_path('../.github/workflows/ci.yml')), 'observability_alerts' => file_exists(base_path('../infra/observability/alerts.yml')), 'backup_runbook' => file_exists(base_path('../docs/backup.md'))];
        return ['status' => 'conditional', 'checks' => $checks, 'reason' => 'Repository configuration is present; runtime health, backup restore and cloud controls require environment evidence.'];
    }
    public function gates(): array {
        $definitions = ['critical_functional'=>'functional','financial_integrity'=>'financial_integrity','authorization_regression'=>'security','performance_evaluated'=>'performance','backup_restore_verified'=>'recovery','monitoring_configured'=>'monitoring'];
        $result = [];
        foreach ($definitions as $key => $category) {
            $stored = QaReleaseGate::where('gate_key',$key)->first();
            if ($stored) { $result[] = $stored; continue; }
            $run = QaTestRun::where('category',$category)->latest('finished_at')->first();
            $status = $run?->status === 'passed' ? 'ready' : 'blocked';
            $result[] = ['gate_key'=>$key,'status'=>$status,'required'=>true,'evidence_reference'=>$run?->artifact_uri,'reason'=>$run ? 'Latest evidence did not pass.' : 'No evidence recorded.'];
        }
        return $result;
    }
    public function testRuns() { return QaTestRun::latest('created_at')->paginate(10); }
    public function findings() { return QaFinding::latest('created_at')->paginate(10); }
    public function performance() { return QaPerformanceRun::latest('created_at')->paginate(10); }
    public function recovery() { return QaRecoveryDrill::latest('created_at')->paginate(10); }
}
