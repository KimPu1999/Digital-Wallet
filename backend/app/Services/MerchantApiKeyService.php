<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantApiKey;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class MerchantApiKeyService
{
    public function __construct(private MerchantAccessService $access, private TransferStepUpService $stepUp) {}

    public function create(User $user, Merchant $merchant, string $name, array $scopes, ?\DateTimeInterface $expiresAt = null, ?string $challengeId = null, ?string $challengeCode = null): array
    {
        $this->access->assert($user, $merchant, ['owner', 'manager', 'developer']);
        $this->stepUp->consume($user, (string) $challengeId, (string) $challengeCode);
        $allowed = ['checkout:write', 'checkout:read', 'refunds:read', 'settlements:read', 'webhooks:write'];
        if (array_diff($scopes, $allowed)) throw ValidationException::withMessages(['scopes' => 'One or more API key scopes are not allowed.']);
        $raw = 'dw_test_'.Str::random(48);
        $key = MerchantApiKey::create(['merchant_id' => $merchant->id, 'created_by' => $user->id, 'name' => $name, 'key_prefix' => substr($raw, 0, 16), 'key_hash' => hash('sha256', $raw), 'scopes' => array_values(array_unique($scopes)), 'expires_at' => $expiresAt]);
        app(FinancialAuditLogger::class)->record('merchant.api_key.created', MerchantApiKey::class, $key->id, ['merchant_id' => $merchant->id, 'scopes' => $key->scopes, 'sandbox_only' => true], actorId: $user->id);
        return ['key' => $key, 'raw_key' => $raw];
    }

    public function revoke(User $user, MerchantApiKey $key, ?string $challengeId = null, ?string $challengeCode = null): MerchantApiKey
    {
        $this->access->assert($user, $key->merchant()->firstOrFail(), ['owner', 'manager', 'developer']);
        $this->stepUp->consume($user, (string) $challengeId, (string) $challengeCode);
        $key->update(['revoked_at' => now()]);
        app(FinancialAuditLogger::class)->record('merchant.api_key.revoked', MerchantApiKey::class, $key->id, ['merchant_id' => $key->merchant_id], actorId: $user->id);
        return $key->refresh();
    }
}
