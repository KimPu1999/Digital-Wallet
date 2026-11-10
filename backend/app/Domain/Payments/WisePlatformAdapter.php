<?php

namespace App\Domain\Payments;

final class WisePlatformAdapter extends ConfiguredPaymentProvider
{
    public function code(): string { return 'wise'; }
}
