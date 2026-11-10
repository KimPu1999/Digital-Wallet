<?php

namespace App\Domain\Payments;

use App\Models\PaymentIntent;
use App\Models\ProviderAccount;

interface PaymentProviderInterface
{
    public function code(): string;

    public function supports(string $operation): bool;

    public function createDeposit(PaymentIntent $intent, ProviderAccount $account): ProviderOperationResult;

    public function createWithdrawal(PaymentIntent $intent, ProviderAccount $account): ProviderOperationResult;

    public function verifyWebhook(string $rawBody, array $headers, ProviderAccount $account): WebhookVerificationResult;

    public function normalizeWebhook(string $rawBody): ProviderWebhookEvent;
}
