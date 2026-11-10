<?php
namespace App\Policies;
use App\Models\User; use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
final class WalletPolicy { public function view(User $user, Wallet $wallet): bool { $owner=$wallet->owner; if(!$owner) return false; if($owner->owner_type === 'customer') return $owner->user_id === $user->id; return $owner->owner_type === 'merchant' && DB::table('merchant_memberships')->where('merchant_id',$owner->merchant_id)->where('user_id',$user->id)->exists(); } public function update(User $user, Wallet $wallet): bool { return $this->view($user,$wallet); } }
