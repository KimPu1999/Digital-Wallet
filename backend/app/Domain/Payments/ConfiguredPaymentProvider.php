<?php

namespace App\Domain\Payments;

use App\Models\PaymentIntent;
use App\Models\ProviderAccount;

abstract class ConfiguredPaymentProvider implements PaymentProviderInterface
{
    public function supports(string $operation): bool { return false; }

    public function createDeposit(PaymentIntent $intent, ProviderAccount $account): ProviderOperationResult
    {
        throw new UnsupportedPaymentOperation($this->code(), 'deposit');
    }

    public function createWithdrawal(PaymentIntent $intent, ProviderAccount $account): ProviderOperationResult
    {
        throw new UnsupportedPaymentOperation($this->code(), 'withdrawal');
    }

    public function verifyWebhook(string $rawBody, array $headers, ProviderAccount $account): WebhookVerificationResult
    {
        return new WebhookVerificationResult(false, 'Provider adapter is not configured for sandbox execution.');
    }

    public function normalizeWebhook(string $rawBody): ProviderWebhookEvent
    {
        throw new UnsupportedPaymentOperation($this->code(), 'webhook normalization');
    }
}
