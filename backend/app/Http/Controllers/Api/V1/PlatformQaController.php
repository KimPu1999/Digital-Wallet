<?php
namespace App\Http\Controllers\Api\V1;
use App\Domain\QA\QaAssessmentService;
use App\Services\PlatformAdminAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
final class PlatformQaController {
    public function __construct(private QaAssessmentService $qa, private PlatformAdminAccessService $access) {}
    public function overview(Request $request): JsonResponse { $this->access->assert($request->user(),'dashboard.read'); return response()->json(['data'=>$this->qa->overview(),'sandbox_only'=>true]); }
    public function testRuns(Request $request): JsonResponse { $this->access->assert($request->user(),'audit.read'); return $this->page($this->qa->testRuns()); }
    public function findings(Request $request): JsonResponse { $this->access->assert($request->user(),'audit.read'); return $this->page($this->qa->findings()); }
    public function performance(Request $request): JsonResponse { $this->access->assert($request->user(),'audit.read'); return $this->page($this->qa->performance()); }
    public function recovery(Request $request): JsonResponse { $this->access->assert($request->user(),'audit.read'); return $this->page($this->qa->recovery()); }
    public function moduleCoverage(Request $request): JsonResponse { $this->access->assert($request->user(),'audit.read'); return $this->page($this->qa->moduleCoverage()); }
    public function featureInventory(Request $request): JsonResponse { $this->access->assert($request->user(),'audit.read'); return response()->json(['data' => $this->qa->featureInventory(), 'sandbox_only' => true]); }
    public function maintenance(Request $request): JsonResponse { $this->access->assert($request->user(),'audit.read'); return $this->page($this->qa->maintenanceBacklog()); }
    public function financialIntegrity(Request $request): JsonResponse { $this->access->assert($request->user(),'audit.read'); return response()->json(['data' => $this->qa->financialIntegrity(), 'sandbox_only' => true]); }
    public function infrastructure(Request $request): JsonResponse { $this->access->assert($request->user(),'system.health'); return response()->json(['data' => $this->qa->infrastructureReadiness(), 'sandbox_only' => true]); }
    public function releaseReadiness(Request $request): JsonResponse { $this->access->assert($request->user(),'dashboard.read'); return response()->json(['data'=>['status'=>$this->overallStatus(),'gates'=>$this->qa->gates(),'sandbox_only'=>true]]); }
    private function overallStatus(): string { $gates=$this->qa->gates(); return collect($gates)->contains(fn ($gate) => data_get($gate,'status') === 'blocked') ? 'blocked' : (collect($gates)->contains(fn ($gate) => data_get($gate,'status') === 'conditional') ? 'conditional' : 'ready'); }
    private function page($page): JsonResponse { return response()->json(['data'=>$page->items(),'meta'=>['current_page'=>$page->currentPage(),'last_page'=>$page->lastPage(),'per_page'=>$page->perPage(),'total'=>$page->total()],'sandbox_only'=>true]); }
}
