<?php

namespace App\Domain\Payments;

final readonly class ProviderOperationResult
{
    public function __construct(
        public string $status,
        public ?string $reference = null,
        public ?string $failureReason = null,
        public bool $unknown = false,
        public array $metadata = [],
    ) {}
}
