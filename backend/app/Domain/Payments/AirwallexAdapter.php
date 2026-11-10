<?php

namespace App\Domain\Payments;

final class AirwallexAdapter extends ConfiguredPaymentProvider
{
    public function code(): string { return 'airwallex'; }
}
