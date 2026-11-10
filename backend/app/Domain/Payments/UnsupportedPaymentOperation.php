<?php

namespace App\Domain\Payments;

use RuntimeException;

final class UnsupportedPaymentOperation extends RuntimeException
{
    public function __construct(string $provider, string $operation)
    {
        parent::__construct("Payment operation '{$operation}' is not supported by provider '{$provider}'.");
    }
}
