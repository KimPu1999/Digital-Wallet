<?php
namespace App\Domain\Reporting;
use App\Models\ReportAuditEvent;
use App\Models\ReportDefinition;
use App\Models\ReportRun;
use Illuminate\Support\Facades\DB;
final class ReportExecutionService {
    public function __construct(private ReportQueryService $queries) {}
    public function run(string $type, array $parameters, ?string $actorId, string $scope = 'platform', ?string $merchantId = null): ReportRun {
        return DB::transaction(function () use ($type, $parameters, $actorId, $scope, $merchantId): ReportRun {
            $definition = ReportDefinition::firstOrCreate(['code' => $scope.'.'.$type, 'version' => 1], ['name' => ucwords(str_replace(['-', '_'], ' ', $type)), 'report_type' => $type, 'scope' => $scope, 'definition' => ['source' => 'immutable posted ledger and verified sandbox records'], 'status' => 'active', 'created_by' => $actorId]);
            $run = ReportRun::create(['report_definition_id' => $definition->id, 'requested_by' => $actorId, 'scope' => $scope, 'status' => 'running', 'parameters' => $parameters, 'started_at' => now()]);
            $data = match ($type) {
                'overview' => $this->queries->overview($parameters),
                'trial-balance' => $this->queries->trialBalance($parameters),
                'profit-loss' => $this->queries->profitLoss($parameters),
                'balance-sheet' => $this->queries->balanceSheet($parameters),
                'merchant-overview' => $this->queries->merchantOverview($parameters, (string) $merchantId),
                default => ['message' => 'Report type is available through a paginated read API.', 'report_type' => $type, 'sandbox_only' => true],
            };
            $json = json_encode($data, JSON_THROW_ON_ERROR);
            $run->update(['status' => 'completed', 'result_summary' => ['report_type' => $type, 'row_count' => is_countable($data) ? count($data) : 0, 'data' => $data], 'result_checksum' => hash('sha256', $json), 'completed_at' => now(), 'expires_at' => now()->addDays(7)]);
            ReportAuditEvent::create(['report_run_id' => $run->id, 'actor_id' => $actorId, 'action' => 'report.generated', 'metadata' => ['report_type' => $type, 'scope' => $scope, 'sandbox_only' => true], 'created_at' => now()]);
            return $run->fresh()->load('files');
        });
    }
}
