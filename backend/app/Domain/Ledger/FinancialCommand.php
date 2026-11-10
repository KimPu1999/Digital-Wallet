<?php

namespace App\Domain\Ledger;

final readonly class FinancialCommand
{
    /** @param LedgerLine[] $lines */
    public function __construct(
        public string $transactionType,
        public string $reference,
        public array $lines,
        public ?string $description = null,
        public array $metadata = [],
        public bool $checkAvailableFunds = true,
    ) {}
}
