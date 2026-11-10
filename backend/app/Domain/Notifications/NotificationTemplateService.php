<?php
namespace App\Domain\Notifications;
use App\Models\NotificationTemplate;
final class NotificationTemplateService
{
    public function render(string $key, string $locale, array $variables = []): array
    {
        $template = NotificationTemplate::where('template_key', $key)->where('locale', $locale)->where('status', 'active')->latest('version')->first()
            ?: NotificationTemplate::where('template_key', $key)->where('locale', 'en')->where('status', 'active')->latest('version')->first();
        if (!$template) return ['subject' => $key, 'in_app_body' => 'A new account update is available.', 'mandatory' => true];
        $replace = static fn (?string $value): ?string => $value === null ? null : strtr($value, collect($variables)->mapWithKeys(fn ($v, $k) => ['{{'.$k.'}}' => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')])->all());
        return ['subject' => $replace($template->subject), 'email_body' => $replace($template->email_body), 'sms_body' => $replace($template->sms_body), 'push_title' => $replace($template->push_title), 'push_body' => $replace($template->push_body), 'in_app_body' => $replace($template->in_app_body), 'mandatory' => (bool) $template->mandatory];
    }
}
