<?php

namespace App\Jobs;

use App\Domain\Payments\ProviderWebhookEvent;
use App\Models\PaymentWebhookEvent;
use App\Services\PaymentWebhookService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPaymentWebhook implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $eventId, public array $payload) {}

    public function handle(PaymentWebhookService $service): void
    {
        $record = PaymentWebhookEvent::find($this->eventId);
        if (!$record || $record->status === 'processed') return;
        $payload = $this->payload;
        $event = new ProviderWebhookEvent(
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
        $service->process($record, $event);
    }
}
