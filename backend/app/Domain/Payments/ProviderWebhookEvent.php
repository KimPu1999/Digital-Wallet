<?php

namespace App\Domain\Payments;

use Carbon\CarbonImmutable;

final readonly class ProviderWebhookEvent
{
    public function __construct(
        public string $eventId,
        public string $eventType,
        public string $status,
        public string $intentReference,
        public int $amountMinor,
        public string $currencyCode,
        public int $feeMinor = 0,
        public ?CarbonImmutable $occurredAt = null,
        public array $payload = [],
    ) {}
}
