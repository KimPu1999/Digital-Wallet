<?php

namespace App\Services;

use App\Jobs\ProcessPaymentWebhook;
use App\Domain\Payments\PaymentProviderRegistry;
use App\Models\PaymentIntent;
use App\Models\PaymentProvider;
use App\Models\PaymentWebhookEvent;
use App\Models\ProviderAccount;
use App\Models\ProviderTransaction;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PaymentWebhookService
{
    public function __construct(private PaymentProviderRegistry $registry, private PaymentSettlementService $settlements) {}

    public function receive(PaymentProvider $provider, ProviderAccount $account, string $rawBody, array $headers): array
    {
        $adapter = $this->registry->resolve($provider);
        $verification = $adapter->verifyWebhook($rawBody, $headers, $account);
        if (!$verification->valid) throw ValidationException::withMessages(['webhook' => $verification->reason ?? 'Webhook verification failed.']);
        try {
            $event = $adapter->normalizeWebhook($rawBody);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['webhook' => 'Webhook payload is invalid.']);
        }
        if ($event->eventId === '' || $event->intentReference === '') throw ValidationException::withMessages(['webhook' => 'Webhook is missing a stable event or intent reference.']);

        $existing = PaymentWebhookEvent::where('provider_id', $provider->id)->where('event_id', $event->eventId)->first();
        if ($existing) return ['event' => $existing, 'duplicate' => true];
        $record = PaymentWebhookEvent::create(['provider_id' => $provider->id, 'event_id' => $event->eventId, 'event_type' => $event->eventType, 'signature_valid' => true, 'status' => 'queued', 'raw_body_encrypted' => Crypt::encryptString($rawBody), 'received_at' => now(), 'event_occurred_at' => $event->occurredAt, 'metadata' => ['sandbox_only' => true]]);
        ProcessPaymentWebhook::dispatch($record->id, $event->payload);
        return ['event' => $record->refresh(), 'duplicate' => false];
    }

    public function process(PaymentWebhookEvent $record, \App\Domain\Payments\ProviderWebhookEvent $event): void
    {
        DB::transaction(function () use ($record, $event): void {
            $intent = PaymentIntent::where('intent_reference', $event->intentReference)->where('provider_id', $record->provider_id)->lockForUpdate()->first();
            if (!$intent) {
                $record->update(['status' => 'failed', 'error_message' => 'Payment intent was not found.', 'processed_at' => now()]);
                return;
            }
            if ($event->amountMinor !== (int) $intent->amount_minor || $event->currencyCode !== $intent->currency_code) {
                $record->update(['status' => 'failed', 'error_message' => 'Webhook amount or currency did not match the payment intent.', 'processed_at' => now()]);
                app(PaymentReconciliationService::class)->exception($intent, null, 'webhook_mismatch', 'Webhook amount or currency did not match the payment intent.', (int) $intent->amount_minor, $event->amountMinor);
                return;
            }
            $providerTransaction = ProviderTransaction::firstOrCreate(['provider_id' => $record->provider_id, 'provider_event_id' => $event->eventId], ['payment_intent_id' => $intent->id, 'payment_attempt_id' => $intent->attempts()->latest('attempt_number')->value('id'), 'external_reference' => $intent->external_reference ?? $intent->intent_reference, 'status' => $event->status, 'currency_code' => $event->currencyCode, 'amount_minor' => $event->amountMinor, 'fee_minor' => $event->feeMinor, 'observed_at' => $event->occurredAt ?? now(), 'raw_payload' => $event->payload, 'metadata' => ['verified_webhook_id' => $record->id, 'sandbox_only' => true]]);
            $this->settlements->apply($intent, $providerTransaction, $event);
            $record->update(['status' => 'processed', 'processed_at' => now()]);
        });
    }
}
