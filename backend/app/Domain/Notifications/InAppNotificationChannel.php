<?php
namespace App\Domain\Notifications;
use App\Models\User;
final class InAppNotificationChannel implements NotificationChannelInterface { public function name(): string { return 'in_app'; } public function deliver(User $user, array $payload): array { return ['status' => 'delivered', 'provider_reference' => 'database-notification']; } }
