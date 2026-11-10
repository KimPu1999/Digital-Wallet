<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\QA\MobileQaService;
use App\Services\PlatformAdminAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlatformMobileQaController
{
    public function __construct(private MobileQaService $mobile, private PlatformAdminAccessService $access) {}

    public function overview(Request $request): JsonResponse
    {
        $this->access->assert($request->user(), 'dashboard.read');
        return response()->json(['data' => $this->mobile->overview(), 'sandbox_only' => true]);
    }

    public function releaseReadiness(Request $request): JsonResponse
    {
        $this->access->assert($request->user(), 'dashboard.read');
        $gates = $this->mobile->gates();
        $status = collect($gates)->contains(fn (array $gate): bool => $gate['status'] === 'blocked') ? 'blocked' : 'ready';
        return response()->json(['data' => ['status' => $status, 'gates' => $gates, 'sandbox_only' => true]]);
    }

    public function builds(Request $request): JsonResponse { $this->assertAudit($request); return $this->page($this->mobile->builds()); }
    public function crashes(Request $request): JsonResponse { $this->assertAudit($request); return $this->page($this->mobile->crashes()); }
    public function feedback(Request $request): JsonResponse { $this->assertAudit($request); return $this->page($this->mobile->feedback()); }
    public function candidates(Request $request): JsonResponse { $this->assertAudit($request); return $this->page($this->mobile->candidates()); }

    private function assertAudit(Request $request): void
    {
        $this->access->assert($request->user(), 'audit.read');
    }

    private function page($page): JsonResponse
    {
        return response()->json(['data' => $page->items(), 'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()], 'sandbox_only' => true]);
    }
}
