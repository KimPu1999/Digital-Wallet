<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\PaymentIntentRequest;
use App\Http\Requests\WithdrawalIntentRequest;
use App\Http\Resources\PaymentIntentResource;
use App\Models\PaymentIntent;
use App\Models\PaymentProvider;
use App\Models\WalletCurrencyAccount;
use App\Services\PaymentOrchestrator;
use App\Services\PaymentWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentController
{
    public function methods(): JsonResponse
    {
        $providers = PaymentProvider::where('enabled', true)->where('environment', 'sandbox')->where('status', 'active')->get();
        return response()->json(['data' => $providers->flatMap(fn ($provider) => $provider->supported_payment_methods ?? [])->unique()->values()->map(fn ($method) => ['code' => $method, 'sandbox_only' => true]), 'sandbox_only' => true]);
    }

    public function deposit(PaymentIntentRequest $request, PaymentOrchestrator $orchestrator): JsonResponse
    {
        $account = WalletCurrencyAccount::findOrFail($request->string('wallet_currency_account_id')->toString());
        $intent = $orchestrator->createDeposit($request->user(), $account, (int) $request->integer('amount_minor'), $request->string('payment_method')->toString(), (string) $request->header('Idempotency-Key'), $request->string('provider_scenario', 'processing')->toString());
        return response()->json(['data' => new PaymentIntentResource($intent), 'sandbox_only' => true], 201);
    }

    public function withdrawal(WithdrawalIntentRequest $request, PaymentOrchestrator $orchestrator): JsonResponse
    {
        $account = WalletCurrencyAccount::findOrFail($request->string('wallet_currency_account_id')->toString());
        $intent = $orchestrator->createWithdrawal($request->user(), $account, (int) $request->integer('amount_minor'), $request->string('payment_method')->toString(), (string) $request->header('Idempotency-Key'), $request->string('challenge_id')->toString(), $request->string('challenge_code')->toString(), $request->string('provider_scenario', 'processing')->toString());
        return response()->json(['data' => new PaymentIntentResource($intent), 'sandbox_only' => true], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $query = PaymentIntent::with(['provider', 'attempts'])->where('user_id', $request->user()->id)->latest();
        if ($request->filled('operation')) $query->where('operation', $request->string('operation')->toString());
        if ($request->filled('status')) $query->where('status', $request->string('status')->toString());
        $rows = $query->paginate(10);
        return response()->json(['data' => PaymentIntentResource::collection($rows->items()), 'meta' => ['current_page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'per_page' => 10, 'total' => $rows->total()], 'sandbox_only' => true]);
    }

    public function show(Request $request, PaymentIntent $payment): JsonResponse
    {
        abort_unless(app(\App\Policies\PaymentPolicy::class)->view($request->user(), $payment), 404, 'Payment not found.');
        return response()->json(['data' => new PaymentIntentResource($payment->load(['provider', 'attempts', 'providerTransactions'])), 'sandbox_only' => true]);
    }

    public function timeline(Request $request, PaymentIntent $payment): JsonResponse
    {
        abort_unless(app(\App\Policies\PaymentPolicy::class)->view($request->user(), $payment), 404, 'Payment not found.');
        return response()->json(['data' => ['attempts' => $payment->attempts()->latest()->get(), 'provider_transactions' => $payment->providerTransactions()->latest()->get(), 'webhooks' => $payment->webhookEvents()->latest()->get(), 'settlements' => $payment->settlementItems()->latest()->get()], 'sandbox_only' => true]);
    }

    public function webhook(string $provider, Request $request, PaymentWebhookService $service): JsonResponse
    {
        $paymentProvider = PaymentProvider::where('code', $provider)->where('environment', 'sandbox')->firstOrFail();
        $account = $paymentProvider->accounts()->where('environment', 'sandbox')->where('status', 'active')->firstOrFail();
        $headers = [];
        foreach ($request->headers->all() as $name => $values) $headers[$name] = is_array($values) ? ($values[0] ?? '') : $values;
        $result = $service->receive($paymentProvider, $account, $request->getContent(), $headers);
        return response()->json(['data' => ['event_id' => $result['event']->event_id, 'status' => $result['event']->status, 'duplicate' => $result['duplicate']], 'sandbox_only' => true], $result['duplicate'] ? 200 : 202);
    }
}
