<?php
namespace App\Domain\Notifications;
use App\Models\Notification;
use App\Models\NotificationEventReceipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
final class NotificationEventService
{
    public function __construct(private NotificationTemplateService $templates, private NotificationDeliveryService $deliveries) {}
    public function emit(User $user, string $eventKey, string $eventType, string $templateKey, array $variables = [], string $category = 'support', bool $mandatory = false): Notification
    {
        return DB::transaction(function () use ($user, $eventKey, $eventType, $templateKey, $variables, $category, $mandatory): Notification {
            $receipt = NotificationEventReceipt::where('event_key', $eventKey)->lockForUpdate()->first();
            if ($receipt?->notification_id) return Notification::findOrFail($receipt->notification_id);
            $locale = $variables['locale'] ?? 'en'; $content = $this->templates->render($templateKey, $locale, $variables);
            $notification = Notification::create(['id' => (string) Str::uuid(), 'type' => $eventType, 'notifiable_type' => User::class, 'notifiable_id' => $user->id, 'data' => ['event_type' => $eventType, 'category' => $category, 'title' => $content['subject'], 'body' => $content['in_app_body'], 'reference' => $variables['reference'] ?? null, 'sandbox_only' => true]]);
            NotificationEventReceipt::create(['event_key' => $eventKey, 'event_type' => $eventType, 'user_id' => $user->id, 'notification_id' => $notification->id, 'processed_at' => now(), 'metadata' => ['sandbox_only' => true]]);
            $this->deliveries->deliver($notification, $user, $content, $category, $mandatory || $content['mandatory']);
            return $notification;
        });
    }
}
