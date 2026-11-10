<?php

namespace App\Domain\Payments;

final class StripePaymentAdapter extends ConfiguredPaymentProvider
{
    public function code(): string { return 'stripe'; }
}
