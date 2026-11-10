<?php
namespace App\Domain\Notifications;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\User;
final class NotificationDeliveryService
{
    public function __construct(private NotificationPreferenceService $preferences, private InAppNotificationChannel $inApp, private EmailNotificationChannel $email, private SmsNotificationChannel $sms, private PushNotificationChannel $push) {}
    public function deliver(Notification $notification, User $user, array $payload, string $category, bool $mandatory = false): void
    {
        $channels = ['in_app' => $this->inApp, 'email' => $this->email, 'sms' => $this->sms, 'push' => $this->push];
        foreach ($channels as $channel => $adapter) {
            if ($channel !== 'in_app' && !$this->preferences->allows($user, $category, $channel, $mandatory)) continue;
            $result = $adapter->deliver($user, $payload);
            NotificationDelivery::create(['notification_id' => $notification->id, 'user_id' => $user->id, 'channel' => $channel, 'status' => $result['status'], 'attempts' => 1, 'delivered_at' => $result['status'] === 'delivered' ? now() : null, 'provider_reference' => $result['provider_reference'] ?? null, 'metadata' => $result['metadata'] ?? ['sandbox_only' => true]]);
        }
    }
}
