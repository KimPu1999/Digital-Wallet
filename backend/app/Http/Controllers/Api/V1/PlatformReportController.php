<?php
namespace App\Http\Controllers\Api\V1;
use App\Domain\Reporting\ReportExportService;
use App\Domain\Reporting\ReportExecutionService;
use App\Domain\Reporting\ReportPermissionService;
use App\Domain\Reporting\ReportQueryService;
use App\Http\Requests\ReportRequest;
use App\Http\Requests\ReportRunRequest;
use App\Models\ReportDefinition;
use App\Models\ReportRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
final class PlatformReportController {
    public function __construct(private ReportQueryService $queries, private ReportPermissionService $permissions) {}
    public function overview(ReportRequest $request): JsonResponse { $this->permissions->platform($request->user()); return $this->ok($this->queries->overview($request->validated())); }
    public function revenue(ReportRequest $request): JsonResponse { $this->permissions->platform($request->user()); return $this->ok($this->queries->profitLoss($request->validated())); }
    public function transactionVolume(ReportRequest $request): JsonResponse { $this->permissions->platform($request->user()); return $this->ok($this->queries->overview($request->validated())['transaction_volume_by_currency']); }
    public function currencyExposure(ReportRequest $request): JsonResponse { $this->permissions->platform($request->user()); return $this->ok($this->queries->balanceSheet($request->validated())); }
    public function trialBalance(ReportRequest $request): JsonResponse { $this->permissions->platform($request->user()); return $this->ok($this->queries->trialBalance($request->validated())); }
    public function generalLedger(ReportRequest $request): JsonResponse { $this->permissions->platform($request->user()); return $this->page($this->queries->generalLedger($request->validated())); }
    public function profitLoss(ReportRequest $request): JsonResponse { return $this->revenue($request); }
    public function balanceSheet(ReportRequest $request): JsonResponse { $this->permissions->platform($request->user()); return $this->ok($this->queries->balanceSheet($request->validated())); }
    public function wallets(ReportRequest $request): JsonResponse { $this->permissions->platform($request->user()); return $this->page($this->queries->wallets($request->validated())); }
    public function holds(ReportRequest $request): JsonResponse { $this->permissions->platform($request->user()); return $this->page($this->queries->walletHolds($request->validated())); }
    public function transfers(ReportRequest $request): JsonResponse { $this->permissions->platform($request->user()); return $this->page($this->queries->transfers($request->validated())); }
    public function fx(ReportRequest $request): JsonResponse { $this->permissions->platform($request->user()); return $this->page($this->queries->fx($request->validated())); }
    public function payments(ReportRequest $request): JsonResponse { $this->permissions->platform($request->user()); return $this->page($this->queries->payments($request->validated())); }
    public function refunds(ReportRequest $request): JsonResponse { $this->permissions->platform($request->user()); return $this->page($this->queries->refunds($request->validated())); }
    public function settlements(ReportRequest $request): JsonResponse { $this->permissions->platform($request->user()); return $this->page($this->queries->settlements($request->validated())); }
    public function reconciliation(ReportRequest $request): JsonResponse { $this->permissions->platform($request->user(), 'reconciliation.read'); return $this->page($this->queries->reconciliation($request->validated())); }
    public function definitions(Request $request): JsonResponse { $this->permissions->platform($request->user()); return $this->page(ReportDefinition::where('status', 'active')->latest()->paginate(10)); }
    public function createRun(ReportRunRequest $request, ReportExecutionService $execution): JsonResponse { $this->permissions->platform($request->user(), 'financial.read'); $run = $execution->run($request->string('report_type')->toString(), $request->validated(), $request->user()->id, 'platform', $request->input('merchant_id')); return response()->json(['data' => $run, 'sandbox_only' => true], 201); }
    public function runs(Request $request): JsonResponse { $this->permissions->platform($request->user()); return $this->page(ReportRun::with('files')->latest()->paginate(10)); }
    public function run(Request $request, ReportRun $run): JsonResponse { $this->permissions->platform($request->user()); return response()->json(['data' => $run->load('files'), 'sandbox_only' => true]); }
    public function download(Request $request, ReportRun $run, ReportExportService $exports): mixed { $this->permissions->platform($request->user(), 'financial.read'); abort_if($run->expires_at && now()->greaterThan($run->expires_at), 410, 'Report export has expired.'); $file = $run->files()->where('status', 'ready')->latest()->first(); if (!$file) { $data = data_get($run->result_summary, 'data', [['report_type' => data_get($run->result_summary, 'report_type'), 'checksum' => $run->result_checksum]]); if (!is_array($data) || (isset($data['rows']) && is_array($data['rows']))) $data = $data['rows'] ?? [$data]; $file = $exports->csv($run, array_values($data), $request->user()->id); } abort_unless(Storage::disk('local')->exists($file->storage_key), 404, 'Report file is unavailable.'); return Storage::disk('local')->download($file->storage_key, 'report-'.$run->id.'.csv', ['Content-Type' => 'text/csv']); }
    private function ok(mixed $data): JsonResponse { return response()->json(['data' => $data, 'sandbox_only' => true]); }
    private function page($page): JsonResponse { return response()->json(['data' => $page->items(), 'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()], 'sandbox_only' => true]); }
}
