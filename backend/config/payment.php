<?php

return [
    'sandbox_only' => true,
    'mock_webhook_secret' => env('PAYMENT_SANDBOX_WEBHOOK_SECRET', 'sandbox-webhook-secret'),
    'webhook_tolerance_seconds' => (int) env('PAYMENT_WEBHOOK_TOLERANCE_SECONDS', 300),
    'providers' => [
        'sandbox-mock' => App\Domain\Payments\MockPaymentProvider::class,
        'stripe' => App\Domain\Payments\StripePaymentAdapter::class,
        'wise' => App\Domain\Payments\WisePlatformAdapter::class,
        'airwallex' => App\Domain\Payments\AirwallexAdapter::class,
    ],
];
