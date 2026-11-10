<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\CheckoutSessionRequest;
use App\Http\Requests\MerchantApiKeyRequest;
use App\Http\Requests\MerchantPaymentLinkRequest;
use App\Http\Requests\MerchantQrRequest;
use App\Http\Requests\MerchantRefundRequest;
use App\Http\Requests\MerchantWebhookEndpointRequest;
use App\Http\Requests\WalletCheckoutRequest;
use App\Http\Resources\CheckoutSessionResource;
use App\Http\Resources\MerchantApiKeyResource;
use App\Http\Resources\MerchantPaymentLinkResource;
use App\Http\Resources\MerchantQrResource;
use App\Http\Resources\MerchantSettlementResource;
use App\Http\Resources\MerchantWebhookEndpointResource;
use App\Http\Resources\RefundRequestResource;
use App\Models\CheckoutSession;
use App\Models\Merchant;
use App\Models\MerchantApiKey;
use App\Models\MerchantPaymentLink;
use App\Models\MerchantQrCode;
use App\Models\MerchantSettlement;
use App\Models\MerchantWebhookEndpoint;
use App\Models\PaymentIntent;
use App\Models\RefundRequest;
use App\Models\WalletCurrencyAccount;
use App\Services\MerchantApiKeyService;
use App\Services\MerchantCheckoutService;
use App\Services\MerchantSettlementService;
use App\Services\MerchantWebhookService;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MerchantCheckoutController
{
    public function links(Request $request, Merchant $merchant): JsonResponse
    {
        app(\App\Services\MerchantAccessService::class)->assert($request->user(), $merchant);
        $rows = $merchant->paymentLinks()->latest()->paginate(10);
        return response()->json($this->paginated(MerchantPaymentLinkResource::collection($rows->items()), $rows));
    }

    public function createLink(MerchantPaymentLinkRequest $request, Merchant $merchant, MerchantCheckoutService $service): JsonResponse
    {
        $result = $service->createLink($request->user(), $merchant, $request->validated());
        return response()->json(['data' => new MerchantPaymentLinkResource($result['link']), 'public_token' => $result['public_token'], 'sandbox_only' => true], 201);
    }

    public function updateLink(Request $request, Merchant $merchant, MerchantPaymentLink $link): JsonResponse
    {
        app(\App\Services\MerchantAccessService::class)->assert($request->user(), $merchant, ['owner', 'manager', 'finance']);
        abort_unless((string) $link->merchant_id === (string) $merchant->id, 404, 'Payment link not found.');
        $data = $request->validate(['status' => ['sometimes', 'in:active,inactive'], 'description' => ['sometimes', 'nullable', 'string', 'max:255'], 'expires_at' => ['sometimes', 'nullable', 'date', 'after:now']]);
        $link->update($data);
        return response()->json(['data' => new MerchantPaymentLinkResource($link->refresh()), 'sandbox_only' => true]);
    }

    public function sessions(Request $request, Merchant $merchant): JsonResponse
    {
        app(\App\Services\MerchantAccessService::class)->assert($request->user(), $merchant);
        $rows = $merchant->hasMany(CheckoutSession::class)->latest()->paginate(10);
        return response()->json($this->paginated(CheckoutSessionResource::collection($rows->items()), $rows));
    }

    public function createSession(CheckoutSessionRequest $request, Merchant $merchant, MerchantCheckoutService $service): JsonResponse
    {
        $link = $request->filled('payment_link_id') ? MerchantPaymentLink::findOrFail($request->string('payment_link_id')->toString()) : null;
        $result = $service->createSession($request->user(), $merchant, $request->validated(), $link);
        return response()->json(['data' => new CheckoutSessionResource($result['session']), 'public_token' => $result['public_token'], 'checkout_url' => url('/checkout/'.$result['public_token']), 'sandbox_only' => true], 201);
    }

    public function qrs(Request $request, Merchant $merchant): JsonResponse
    {
        app(\App\Services\MerchantAccessService::class)->assert($request->user(), $merchant);
        $rows = $merchant->hasMany(MerchantQrCode::class)->latest()->paginate(10);
        return response()->json($this->paginated(MerchantQrResource::collection($rows->items()), $rows));
    }

    public function createQr(MerchantQrRequest $request, Merchant $merchant, MerchantCheckoutService $service): JsonResponse
    {
        $result = $service->createQr($request->user(), $merchant, $request->validated());
        return response()->json(['data' => new MerchantQrResource($result['qr']), 'public_token' => $result['public_token'], 'checkout_token' => $result['checkout_token'], 'sandbox_only' => true], 201);
    }

    public function refunds(Request $request, Merchant $merchant): JsonResponse
    {
        app(\App\Services\MerchantAccessService::class)->assert($request->user(), $merchant, ['owner', 'manager', 'finance', 'analyst']);
        $rows = RefundRequest::where('merchant_id', $merchant->id)->latest()->paginate(10);
        return response()->json($this->paginated(RefundRequestResource::collection($rows->items()), $rows));
    }

    public function requestRefund(MerchantRefundRequest $request, Merchant $merchant, RefundService $service): JsonResponse
    {
        abort_if(blank($request->header('Idempotency-Key')), 422, 'Idempotency-Key is required.');
        $intent = PaymentIntent::findOrFail($request->string('payment_intent_id')->toString());
        $refund = $service->request($request->user(), $merchant, $intent, (int) $request->integer('amount_minor'), $request->string('reason')->toString(), (string) $request->header('Idempotency-Key'));
        return response()->json(['data' => new RefundRequestResource($refund), 'sandbox_only' => true], 201);
    }

    public function approveRefund(Request $request, Merchant $merchant, RefundRequest $refund, RefundService $service): JsonResponse
    {
        abort_unless((string) $refund->merchant_id === (string) $merchant->id, 404, 'Refund not found.');
        return response()->json(['data' => new RefundRequestResource($service->approve($request->user(), $refund)), 'sandbox_only' => true]);
    }

    public function settlements(Request $request, Merchant $merchant, MerchantSettlementService $service): JsonResponse
    {
        $rows = $service->report($request->user(), $merchant, $request->query('from'), $request->query('to'));
        return response()->json(['data' => MerchantSettlementResource::collection($rows), 'meta' => ['status' => 'projected', 'sandbox_only' => true], 'sandbox_only' => true]);
    }

    public function analytics(Request $request, Merchant $merchant): JsonResponse
    {
        app(\App\Services\MerchantAccessService::class)->assert($request->user(), $merchant);
        $query = PaymentIntent::where('merchant_id', $merchant->id)->where('operation', 'merchant_payment');
        if ($request->filled('from')) $query->where('created_at', '>=', now()->parse($request->query('from'))->startOfDay());
        if ($request->filled('to')) $query->where('created_at', '<=', now()->parse($request->query('to'))->endOfDay());
        $payments = (clone $query)->get(['status', 'amount_minor', 'currency_code']);
        $successful = $payments->where('status', 'succeeded');
        return response()->json(['data' => ['total_payments' => $payments->count(), 'successful_payments' => $successful->count(), 'pending_payments' => $payments->whereIn('status', ['processing', 'pending'])->count(), 'failed_payments' => $payments->whereIn('status', ['failed', 'cancelled'])->count(), 'gross_by_currency' => $successful->groupBy('currency_code')->map(fn ($items) => (int) $items->sum('amount_minor')), 'sandbox_only' => true], 'sandbox_only' => true]);
    }

    public function apiKeys(Request $request, Merchant $merchant): JsonResponse
    {
        app(\App\Services\MerchantAccessService::class)->assert($request->user(), $merchant, ['owner', 'manager', 'developer', 'analyst']);
        return response()->json(['data' => MerchantApiKeyResource::collection($merchant->hasMany(MerchantApiKey::class)->latest()->paginate(10)->items()), 'sandbox_only' => true]);
    }

    public function createApiKey(MerchantApiKeyRequest $request, Merchant $merchant, MerchantApiKeyService $service): JsonResponse
    {
        $result = $service->create($request->user(), $merchant, $request->string('name')->toString(), $request->input('scopes', []), $request->date('expires_at'), $request->string('challenge_id')->toString(), $request->string('challenge_code')->toString());
        return response()->json(['data' => new MerchantApiKeyResource($result['key']), 'raw_key' => $result['raw_key'], 'warning' => 'Store this test key now; it will not be shown again.', 'sandbox_only' => true], 201);
    }

    public function revokeApiKey(Request $request, Merchant $merchant, MerchantApiKey $key, MerchantApiKeyService $service): JsonResponse
    {
        abort_unless((string) $key->merchant_id === (string) $merchant->id, 404, 'API key not found.');
        $data = $request->validate(['challenge_id' => ['required', 'uuid'], 'challenge_code' => ['required', 'string', 'max:64']]);
        return response()->json(['data' => new MerchantApiKeyResource($service->revoke($request->user(), $key, $data['challenge_id'], $data['challenge_code'])), 'sandbox_only' => true]);
    }

    public function webhookEndpoints(Request $request, Merchant $merchant): JsonResponse
    {
        app(\App\Services\MerchantAccessService::class)->assert($request->user(), $merchant, ['owner', 'manager', 'developer', 'analyst']);
        return response()->json(['data' => MerchantWebhookEndpointResource::collection($merchant->hasMany(MerchantWebhookEndpoint::class)->latest()->paginate(10)->items()), 'sandbox_only' => true]);
    }

    public function createWebhookEndpoint(MerchantWebhookEndpointRequest $request, Merchant $merchant, MerchantWebhookService $service): JsonResponse
    {
        $result = $service->register($request->user(), $merchant, $request->string('url')->toString(), $request->input('events', []), $request->string('challenge_id')->toString(), $request->string('challenge_code')->toString());
        return response()->json(['data' => new MerchantWebhookEndpointResource($result['endpoint']), 'signing_secret' => $result['signing_secret'], 'warning' => 'Store this sandbox signing secret now; it will not be shown again.', 'sandbox_only' => true], 201);
    }

    public function publicCheckout(string $token, MerchantCheckoutService $service): JsonResponse
    {
        $session = $service->resolveSession($token);
        return response()->json(['data' => new CheckoutSessionResource($session), 'sandbox_only' => true, 'payment_methods' => $session->allowed_payment_methods ?? ['wallet'], 'hosted_provider_collection' => false]);
    }

    public function walletPay(WalletCheckoutRequest $request, string $token, MerchantCheckoutService $service): JsonResponse
    {
        $session = $service->resolveSession($token);
        $source = WalletCurrencyAccount::findOrFail($request->string('source_wallet_currency_account_id')->toString());
        $intent = $service->payWallet($request->user(), $session, $source, (string) $request->header('Idempotency-Key'), $request->string('challenge_id')->toString(), $request->string('challenge_code')->toString());
        return response()->json(['data' => new \App\Http\Resources\PaymentIntentResource($intent), 'sandbox_only' => true]);
    }

    public function publicQr(string $token, MerchantCheckoutService $service): JsonResponse
    {
        $qr = $service->resolveQr($token);
        return response()->json(['data' => new MerchantQrResource($qr), 'checkout' => $qr->checkoutSession ? new CheckoutSessionResource($qr->checkoutSession) : null, 'sandbox_only' => true]);
    }

    public function qrWalletPay(WalletCheckoutRequest $request, string $token, MerchantCheckoutService $service): JsonResponse
    {
        $qr = $service->resolveQr($token);
        abort_unless($qr->checkoutSession, 422, 'Static QR references require a new checkout session and are not payable directly.');
        $source = WalletCurrencyAccount::findOrFail($request->string('source_wallet_currency_account_id')->toString());
        $intent = $service->payWallet($request->user(), $qr->checkoutSession, $source, (string) $request->header('Idempotency-Key'), $request->string('challenge_id')->toString(), $request->string('challenge_code')->toString());
        return response()->json(['data' => new \App\Http\Resources\PaymentIntentResource($intent), 'sandbox_only' => true]);
    }

    private function paginated($data, $rows): array
    {
        return ['data' => $data, 'meta' => ['current_page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'per_page' => $rows->perPage(), 'total' => $rows->total()], 'sandbox_only' => true];
    }
}
