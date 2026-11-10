<?php

namespace App\Services;

use App\Models\PaymentProvider;

final class ProviderCapabilityService
{
    public function supports(PaymentProvider $provider, string $operation, string $countryCode, string $currencyCode, string $paymentMethod): bool
    {
        if (!$provider->enabled || $provider->status !== 'active' || $provider->environment !== 'sandbox') return false;
        if ($provider->supported_operations && !in_array($operation, $provider->supported_operations, true)) return false;
        if ($provider->supported_currencies && !in_array(strtoupper($currencyCode), array_map('strtoupper', $provider->supported_currencies), true)) return false;
        if ($provider->supported_countries && !in_array(strtoupper($countryCode), array_map('strtoupper', $provider->supported_countries), true)) return false;
        if ($provider->supported_payment_methods && !in_array($paymentMethod, $provider->supported_payment_methods, true)) return false;

        $query = $provider->capabilities()->where('operation', $operation)->where('is_supported', true);
        $query->where(function ($q) use ($countryCode): void { $q->whereNull('country_code')->orWhere('country_code', strtoupper($countryCode)); });
        $query->where(function ($q) use ($currencyCode): void { $q->whereNull('currency_code')->orWhere('currency_code', strtoupper($currencyCode)); });
        $query->where(function ($q) use ($paymentMethod): void { $q->whereNull('payment_method')->orWhere('payment_method', $paymentMethod); });
        return $query->exists();
    }
}
