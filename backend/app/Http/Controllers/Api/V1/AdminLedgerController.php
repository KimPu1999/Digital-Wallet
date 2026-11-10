<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\LedgerAccountResource;
use App\Http\Resources\LedgerTransactionResource;
use App\Models\FinancialAuditEvent;
use App\Models\LedgerAccount;
use App\Models\LedgerTransaction;
use App\Models\ReconciliationRun;
use App\Services\ReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminLedgerController
{
    public function accounts(Request $request): JsonResponse
    {
        $this->authorizeStaff($request);
        $query = LedgerAccount::query()->when($request->filled('currency'), fn ($q) => $q->where('currency_code', strtoupper((string) $request->input('currency'))))->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))->latest();
        $page = $query->paginate(10);
        return response()->json(['data' => LedgerAccountResource::collection($page->items()), 'meta' => $this->meta($page)]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $this->authorizeStaff($request);
        $page = LedgerTransaction::with('entries.account')->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))->when($request->filled('type'), fn ($q) => $q->where('transaction_type', $request->input('type')))->latest()->paginate(10);
        return response()->json(['data' => LedgerTransactionResource::collection($page->items()), 'meta' => $this->meta($page)]);
    }

    public function transaction(Request $request, LedgerTransaction $transaction): JsonResponse
    {
        $this->authorizeStaff($request);
        return response()->json(['data' => new LedgerTransactionResource($transaction->load('entries.account')), 'sandbox_only' => true]);
    }

    public function reconciliation(Request $request, ReconciliationService $service): JsonResponse
    {
        $this->authorizeStaff($request);
        if ($request->boolean('run')) return response()->json(['data' => $service->run($request->user()->id), 'message' => 'Reconciliation completed. Exceptions are reported, never silently corrected.']);
        $page = ReconciliationRun::with('items')->latest('started_at')->paginate(10);
        return response()->json(['data' => $page->items(), 'meta' => $this->meta($page)]);
    }

    public function auditEvents(Request $request): JsonResponse
    {
        $this->authorizeStaff($request);
        $page = FinancialAuditEvent::latest('created_at')->paginate(10);
        return response()->json(['data' => $page->items(), 'meta' => $this->meta($page)]);
    }

    private function authorizeStaff(Request $request): void { abort_unless(app(\App\Policies\AdminWalletPolicy::class)->viewAny($request->user()), 403); }
    private function meta($page): array { return ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()]; }
}
