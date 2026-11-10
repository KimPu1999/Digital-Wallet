<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantWebhookDelivery;
use App\Models\MerchantWebhookEndpoint;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class MerchantWebhookService
{
    public function __construct(private MerchantAccessService $access, private WebhookUrlValidator $urls, private TransferStepUpService $stepUp) {}

    public function register(User $user, Merchant $merchant, string $url, array $events, ?string $challengeId = null, ?string $challengeCode = null): array
    {
        $this->access->assert($user, $merchant, ['owner', 'manager', 'developer']);
        $this->stepUp->consume($user, (string) $challengeId, (string) $challengeCode);
        $this->urls->assertPublicHttps($url);
        $allowed = ['checkout.succeeded', 'checkout.failed', 'refund.succeeded', 'settlement.projected'];
        if (array_diff($events, $allowed)) throw ValidationException::withMessages(['events' => 'One or more webhook events are not supported.']);
        $secret = 'whsec_test_'.Str::random(48);
        $endpoint = MerchantWebhookEndpoint::create(['merchant_id' => $merchant->id, 'created_by' => $user->id, 'url' => $url, 'signing_secret_encrypted' => Crypt::encryptString($secret), 'events' => array_values(array_unique($events)), 'status' => 'active']);
        app(FinancialAuditLogger::class)->record('merchant.webhook.registered', MerchantWebhookEndpoint::class, $endpoint->id, ['merchant_id' => $merchant->id, 'url_host' => parse_url($url, PHP_URL_HOST), 'sandbox_only' => true], actorId: $user->id);
        return ['endpoint' => $endpoint, 'signing_secret' => $secret];
    }

    public function queue(MerchantWebhookEndpoint $endpoint, string $eventType, array $payload): MerchantWebhookDelivery
    {
        if ($endpoint->status !== 'active' || !in_array($eventType, $endpoint->events, true)) throw ValidationException::withMessages(['webhook' => 'Webhook endpoint is inactive or not subscribed to this event.']);
        $eventId = 'mwe_'.Str::lower(Str::random(24));
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, Crypt::decryptString($endpoint->signing_secret_encrypted));
        return MerchantWebhookDelivery::create(['merchant_webhook_endpoint_id' => $endpoint->id, 'event_id' => $eventId, 'event_type' => $eventType, 'payload_encrypted' => Crypt::encryptString(json_encode(['payload' => $payload, 'timestamp' => $timestamp, 'signature' => $signature], JSON_THROW_ON_ERROR)), 'status' => 'queued', 'next_attempt_at' => now()]);
    }
}
