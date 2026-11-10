<?php

namespace App\Domain\Payments;

final readonly class WebhookVerificationResult
{
    public function __construct(
        public bool $valid,
        public ?string $reason = null,
    ) {}
}
