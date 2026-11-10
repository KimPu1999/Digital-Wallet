<?php

namespace App\Domain\Payments;

use App\Models\PaymentProvider;
use Illuminate\Validation\ValidationException;

final class PaymentProviderRegistry
{
    public function resolve(PaymentProvider $provider): PaymentProviderInterface
    {
        $class = config('payment.providers.'.$provider->code);
        if (!$class || !class_exists($class)) throw ValidationException::withMessages(['provider' => 'Payment provider adapter is not configured.']);
        return app($class);
    }
}
