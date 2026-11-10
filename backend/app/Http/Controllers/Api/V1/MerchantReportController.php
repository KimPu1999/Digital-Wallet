<?php
namespace App\Http\Controllers\Api\V1;
use App\Domain\Reporting\ReportPermissionService;
use App\Domain\Reporting\ReportQueryService;
use App\Models\Merchant;
use App\Http\Requests\ReportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
final class MerchantReportController {
    public function __construct(private ReportQueryService $queries, private ReportPermissionService $permissions) {}
    public function overview(ReportRequest $request): JsonResponse { [$merchant] = $this->context($request); return response()->json(['data' => $this->queries->merchantOverview($request->validated(), $merchant->id), 'sandbox_only' => true]); }
    public function payments(ReportRequest $request): JsonResponse { [$merchant] = $this->context($request); return $this->page($this->queries->payments($request->validated(), $merchant->id)); }
    public function refunds(ReportRequest $request): JsonResponse { [$merchant] = $this->context($request); return $this->page($this->queries->refunds($request->validated(), $merchant->id)); }
    public function settlements(ReportRequest $request): JsonResponse { [$merchant] = $this->context($request); return $this->page($this->queries->settlements($request->validated(), $merchant->id)); }
    private function context(Request $request): array { $id = $request->query('merchant_id'); abort_unless($id, 422, 'merchant_id is required.'); $merchant = Merchant::findOrFail($id); $this->permissions->merchant($request->user(), $merchant); return [$merchant]; }
    private function page($page): JsonResponse { return response()->json(['data' => $page->items(), 'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()], 'sandbox_only' => true]); }
}
