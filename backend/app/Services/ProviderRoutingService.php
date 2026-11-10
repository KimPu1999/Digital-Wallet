<?php

namespace App\Services;

use App\Domain\Payments\PaymentProviderInterface;
use App\Domain\Payments\PaymentProviderRegistry;
use App\Models\PaymentIntent;
use App\Models\ProviderAccount;
use App\Models\PaymentProvider;
use Illuminate\Validation\ValidationException;

final class ProviderRoutingService
{
    public function __construct(private ProviderCapabilityService $capabilities, private PaymentProviderRegistry $registry) {}

    /** @return array{provider: PaymentProvider, account: ProviderAccount, adapter: PaymentProviderInterface} */
    public function route(PaymentIntent $intent): array
    {
        $countryCode = strtoupper((string) ($intent->user?->country_code ?? ''));
        $candidates = PaymentProvider::query()
            ->where('enabled', true)
            ->where('status', 'active')
            ->where('environment', 'sandbox')
            ->whereIn('health_status', ['healthy', 'unknown'])
            ->get()
            ->filter(fn (PaymentProvider $provider): bool => $this->capabilities->supports($provider, $intent->operation, $countryCode, $intent->currency_code, $intent->payment_method));

        $rules = \App\Models\ProviderRoutingRule::query()
            ->where('operation', $intent->operation)
            ->where('is_enabled', true)
            ->orderBy('priority')
            ->get();

        $ordered = $rules->map(fn ($rule) => $candidates->firstWhere('id', $rule->provider_id))->filter()->merge($candidates)->unique('id');
        foreach ($ordered as $provider) {
            $account = $provider->accounts()->where('environment', 'sandbox')->where('status', 'active')->first();
            $adapter = $this->registry->resolve($provider);
            if ($account && $adapter->supports($intent->operation)) return ['provider' => $provider, 'account' => $account, 'adapter' => $adapter];
        }
        throw ValidationException::withMessages(['provider' => 'No eligible sandbox payment provider is available for this currency, country, method, or operation.']);
    }
}
