<?php
namespace App\Domain\Notifications;
use App\Models\User;
final class SmsNotificationChannel implements NotificationChannelInterface { public function name(): string { return 'sms'; } public function deliver(User $user, array $payload): array { return ['status' => 'mock_queued', 'provider_reference' => 'sandbox-sms-adapter', 'metadata' => ['configured' => false, 'sent' => false]]; } }
