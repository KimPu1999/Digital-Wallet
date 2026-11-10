<?php
namespace App\Policies;
use App\Models\User; use App\Models\Wallet;
final class WalletPolicy { public function view(User $user, Wallet $wallet): bool { return $wallet->user_id === $user->id; } }
