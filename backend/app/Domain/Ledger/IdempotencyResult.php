<?php

namespace App\Domain\Ledger;

final readonly class IdempotencyResult
{
    public function __construct(public mixed $value, public bool $replayed = false) {}
}
