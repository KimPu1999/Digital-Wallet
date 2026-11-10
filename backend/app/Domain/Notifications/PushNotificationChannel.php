<?php
namespace App\Domain\Notifications;
use App\Models\User;
final class PushNotificationChannel implements NotificationChannelInterface { public function name(): string { return 'push'; } public function deliver(User $user, array $payload): array { return ['status' => 'mock_queued', 'provider_reference' => 'sandbox-push-adapter', 'metadata' => ['configured' => false, 'sent' => false]]; } }
