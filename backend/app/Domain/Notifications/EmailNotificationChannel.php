<?php
namespace App\Domain\Notifications;
use App\Models\User;
final class EmailNotificationChannel implements NotificationChannelInterface { public function name(): string { return 'email'; } public function deliver(User $user, array $payload): array { return ['status' => 'mock_queued', 'provider_reference' => 'local-mail-log', 'metadata' => ['mail_mailer' => config('mail.default'), 'sent' => false]]; } }
