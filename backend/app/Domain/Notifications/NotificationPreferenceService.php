<?php
namespace App\Domain\Notifications;
use App\Models\NotificationPreference;
use App\Models\User;
final class NotificationPreferenceService
{
    public function get(User $user): NotificationPreference { return NotificationPreference::firstOrCreate(['user_id' => $user->id]); }
    public function update(User $user, array $values): NotificationPreference { $preference = $this->get($user); $preference->fill(collect($values)->only(['transaction_email','transaction_sms','transaction_push','support_email','support_push','product_email','marketing_email','marketing_sms','marketing_push'])->all())->save(); return $preference->refresh(); }
    public function allows(User $user, string $category, string $channel, bool $mandatory = false): bool { if ($mandatory || $category === 'security') return true; $key = $category.'_'.$channel; return (bool) $this->get($user)->getAttribute($key); }
}
