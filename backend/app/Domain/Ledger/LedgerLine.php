<?php

namespace App\Domain\Ledger;

use InvalidArgumentException;

final readonly class LedgerLine
{
    public string $accountId;
    public string $currencyCode;
    public string $side;
    public int $amountMinor;
    public array $metadata;

    public function __construct(string $accountId, string $currencyCode, string $side, int $amountMinor, array $metadata = [])
    {
        if (!in_array($side, ['debit', 'credit'], true)) throw new InvalidArgumentException('Ledger side must be debit or credit.');
        if ($amountMinor <= 0) throw new InvalidArgumentException('Ledger amount must be positive minor units.');
        if ($amountMinor > PHP_INT_MAX) throw new InvalidArgumentException('Ledger amount exceeds integer limits.');
        $this->accountId = $accountId;
        $this->currencyCode = strtoupper($currencyCode);
        $this->side = $side;
        $this->amountMinor = $amountMinor;
        $this->metadata = $metadata;
    }
}
