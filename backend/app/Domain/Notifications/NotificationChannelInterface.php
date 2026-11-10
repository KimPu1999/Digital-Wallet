<?php
namespace App\Domain\Notifications;
use App\Models\User;
interface NotificationChannelInterface { public function name(): string; public function deliver(User $user, array $payload): array; }
