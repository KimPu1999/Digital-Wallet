<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\PaymentIntentResource;
use App\Models\PaymentIntent;
use App\Models\PaymentProvider;
use App\Models\PaymentReconciliationItem;
use App\Models\PaymentWebhookEvent;
use App\Models\SettlementBatch;
use App\Services\PaymentReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminPaymentController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize($request);
        $query = PaymentIntent::with('provider')->latest();
        foreach (['status', 'operation', 'currency_code', 'provider_id'] as $filter) if ($request->filled($filter)) $query->where($filter, $request->string($filter)->toString());
        $rows = $query->paginate(10);
        return response()->json(['data' => PaymentIntentResource::collection($rows->items()), 'meta' => ['current_page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'per_page' => 10, 'total' => $rows->total()], 'sandbox_only' => true]);
    }

    public function show(Request $request, PaymentIntent $payment): JsonResponse
    {
        $this->authorize($request);
        return response()->json(['data' => new PaymentIntentResource($payment->load(['provider', 'attempts', 'providerTransactions', 'settlementItems'])), 'sandbox_only' => true]);
    }

    public function providers(Request $request): JsonResponse
    {
        $this->authorize($request);
        return response()->json(['data' => PaymentProvider::withCount(['accounts', 'healthEvents'])->latest()->get(), 'sandbox_only' => true]);
    }

    public function webhooks(Request $request): JsonResponse
    {
        $this->authorize($request);
        return response()->json(['data' => PaymentWebhookEvent::with('provider')->latest()->paginate(10), 'sandbox_only' => true]);
    }

    public function settlements(Request $request): JsonResponse
    {
        $this->authorize($request);
        return response()->json(['data' => SettlementBatch::with('provider')->latest()->paginate(10), 'sandbox_only' => true]);
    }

    public function reconciliation(Request $request, PaymentReconciliationService $service): JsonResponse
    {
        $this->authorize($request);
        return response()->json(['data' => $service->query()->paginate(10), 'sandbox_only' => true]);
    }

    private function authorize(Request $request): void
    {
        abort_unless(app(\App\Policies\AdminWalletPolicy::class)->viewAny($request->user()), 403);
    }
}
