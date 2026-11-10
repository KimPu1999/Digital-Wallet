<?php

namespace App\Domain\Payments;

use App\Models\PaymentIntent;
use App\Models\ProviderAccount;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;

final class MockPaymentProvider implements PaymentProviderInterface
{
    public function code(): string { return 'sandbox-mock'; }

    public function supports(string $operation): bool { return in_array($operation, ['deposit', 'withdrawal'], true); }

    public function createDeposit(PaymentIntent $intent, ProviderAccount $account): ProviderOperationResult
    {
        return $this->submit($intent, 'deposit');
    }

    public function createWithdrawal(PaymentIntent $intent, ProviderAccount $account): ProviderOperationResult
    {
        return $this->submit($intent, 'withdrawal');
    }

    public function verifyWebhook(string $rawBody, array $headers, ProviderAccount $account): WebhookVerificationResult
    {
        $timestamp = (string) ($headers['x-mock-timestamp'] ?? $headers['X-Mock-Timestamp'] ?? '');
        $signature = (string) ($headers['x-mock-signature'] ?? $headers['X-Mock-Signature'] ?? '');
        if ($timestamp === '' || $signature === '' || !ctype_digit($timestamp)) return new WebhookVerificationResult(false, 'Missing webhook signature headers.');
        if (abs(now()->timestamp - (int) $timestamp) > (int) config('payment.webhook_tolerance_seconds', 300)) return new WebhookVerificationResult(false, 'Webhook timestamp is outside the replay window.');
        $secret = (string) config('payment.mock_webhook_secret');
        $expected = hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);
        return new WebhookVerificationResult(hash_equals($expected, $signature), 'Invalid webhook signature.');
    }

    public function normalizeWebhook(string $rawBody): ProviderWebhookEvent
    {
        $payload = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
        return new ProviderWebhookEvent(
            eventId: (string) ($payload['id'] ?? ''),
            eventType: (string) ($payload['type'] ?? 'payment.updated'),
            status: (string) ($payload['status'] ?? 'unknown'),
            intentReference: (string) ($payload['intent_reference'] ?? ''),
            amountMinor: (int) ($payload['amount_minor'] ?? 0),
            currencyCode: strtoupper((string) ($payload['currency_code'] ?? '')),
            feeMinor: (int) ($payload['fee_minor'] ?? 0),
            occurredAt: isset($payload['occurred_at']) ? CarbonImmutable::parse($payload['occurred_at']) : now()->toImmutable(),
            payload: $payload,
        );
    }

    private function submit(PaymentIntent $intent, string $operation): ProviderOperationResult
    {
        $scenario = (string) ($intent->metadata['provider_scenario'] ?? 'processing');
        $reference = 'sandbox-payment-'.$intent->id;
        return match ($scenario) {
            'failed' => new ProviderOperationResult('failed', $reference, 'Sandbox provider rejected the request.'),
            'unknown', 'timeout' => new ProviderOperationResult('unknown', $reference, 'Sandbox provider outcome is unknown.', true),
            default => new ProviderOperationResult('processing', $reference, null, false, ['operation' => $operation, 'requires_verified_webhook' => true]),
        };
    }
}
