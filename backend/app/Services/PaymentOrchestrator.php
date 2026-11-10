<?php

namespace App\Services;

use App\Domain\Payments\PaymentStateMachine;
use App\Models\Currency;
use App\Models\KycCase;
use App\Models\PaymentIntent;
use App\Models\User;
use App\Models\WalletCurrencyAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PaymentOrchestrator
{
    public function __construct(private IdempotencyService $idempotency, private ProviderRoutingService $routing, private PaymentStateMachine $states, private CountryEligibilityService $eligibility, private WalletHoldService $holds, private TransferStepUpService $stepUp) {}

    public function createDeposit(User $user, WalletCurrencyAccount $account, int $amountMinor, string $paymentMethod, string $idempotencyKey, string $scenario = 'processing'): PaymentIntent
    {
        $this->assertWalletAndEligibility($user, $account, 'deposits_enabled');
        return $this->create($user, $account, $amountMinor, $paymentMethod, 'deposit', $idempotencyKey, null, $scenario);
    }

    public function createWithdrawal(User $user, WalletCurrencyAccount $account, int $amountMinor, string $paymentMethod, string $idempotencyKey, string $challengeId, string $challengeCode, string $scenario = 'processing'): PaymentIntent
    {
        $this->assertWalletAndEligibility($user, $account, 'withdrawals_enabled');
        return $this->create($user, $account, $amountMinor, $paymentMethod, 'withdrawal', $idempotencyKey, [$challengeId, $challengeCode], $scenario);
    }

    private function create(User $user, WalletCurrencyAccount $account, int $amountMinor, string $paymentMethod, string $operation, string $idempotencyKey, ?array $challenge, string $scenario): PaymentIntent
    {
        if ($amountMinor <= 0 || $amountMinor > PHP_INT_MAX) throw ValidationException::withMessages(['amount_minor' => 'Amount must be a positive minor-unit integer.']);
        $payload = ['wallet_currency_account_id' => $account->id, 'amount_minor' => $amountMinor, 'payment_method' => $paymentMethod, 'operation' => $operation, 'scenario' => $scenario];
        $result = $this->idempotency->execute('payment.'.$operation.'.'.$user->id, $idempotencyKey, $payload, function () use ($user, $account, $amountMinor, $paymentMethod, $operation, $idempotencyKey, $challenge, $scenario): array {
            return DB::transaction(function () use ($user, $account, $amountMinor, $paymentMethod, $operation, $idempotencyKey, $challenge, $scenario): array {
                if ($challenge) $this->stepUp->consume($user, $challenge[0], $challenge[1]);
                $lockedAccount = WalletCurrencyAccount::with(['wallet.owner', 'currency'])->whereKey($account->id)->lockForUpdate()->firstOrFail();
                $intent = PaymentIntent::create(['user_id' => $user->id, 'wallet_currency_account_id' => $lockedAccount->id, 'operation' => $operation, 'payment_method' => $paymentMethod, 'currency_code' => $lockedAccount->currency->code, 'amount_minor' => $amountMinor, 'fee_minor' => 0, 'intent_reference' => 'PI-'.strtoupper(Str::random(20)), 'status' => 'created', 'expires_at' => now()->addMinutes(30), 'sandbox_only' => true, 'metadata' => ['provider_scenario' => $scenario, 'sandbox_only' => true]]);
                $key = \App\Models\IdempotencyKey::where('scope', 'payment.'.$operation.'.'.$user->id)->where('key', $idempotencyKey)->first();
                $intent->update(['idempotency_key_id' => $key?->id]);
                if ($operation === 'withdrawal') {
                    $hold = $this->holds->create($user, $lockedAccount, $amountMinor, 'payment:'.$intent->id, $idempotencyKey.':hold', now()->addMinutes(30));
                    $intent->update(['metadata' => ['provider_scenario' => $scenario, 'sandbox_only' => true, 'hold_id' => $hold->id]]);
                }
                $route = $this->routing->route($intent->refresh());
                $intent->update(['provider_id' => $route['provider']->id, 'provider_account_id' => $route['account']->id]);
                $this->submit($intent->refresh(), $route);
                return ['payment_intent_id' => $intent->id];
            });
        });
        return PaymentIntent::with(['provider', 'attempts'])->findOrFail($result->value['payment_intent_id']);
    }

    private function submit(PaymentIntent $intent, array $route): void
    {
        $provider = $route['provider'];
        $account = $route['account'];
        $adapter = $route['adapter'];
        $attempt = $intent->attempts()->create(['provider_id' => $provider->id, 'attempt_number' => ((int) $intent->attempts()->max('attempt_number')) + 1, 'status' => 'created', 'started_at' => now(), 'metadata' => ['sandbox_only' => true]]);
        $result = $intent->operation === 'deposit' ? $adapter->createDeposit($intent, $account) : $adapter->createWithdrawal($intent, $account);
        $attempt->update(['status' => $result->status, 'provider_reference' => $result->reference, 'failure_reason' => $result->failureReason, 'completed_at' => in_array($result->status, ['failed', 'unknown'], true) ? now() : null, 'metadata' => $result->metadata + ['sandbox_only' => true]]);
        $intent->update(['external_reference' => $result->reference]);
        if ($result->status === 'failed') {
            $this->states->transition($intent, 'failed', $result->failureReason);
            if ($intent->operation === 'withdrawal') $this->releaseHold($intent);
        } elseif ($result->status === 'unknown') {
            $this->states->transition($intent, 'unknown', $result->failureReason);
        } else {
            $this->states->transition($intent, 'processing');
        }
    }

    private function releaseHold(PaymentIntent $intent): void
    {
        $holdId = $intent->metadata['hold_id'] ?? null;
        if (!$holdId) return;
        $hold = \App\Models\WalletHold::find($holdId);
        if ($hold && $hold->status === 'active') $this->holds->release($intent->user()->firstOrFail(), $hold, 'payment-withdrawal-release-'.$intent->id);
    }

    private function assertWalletAndEligibility(User $user, WalletCurrencyAccount $account, string $capability): void
    {
        $wallet = $account->wallet()->with('owner')->firstOrFail();
        abort_unless(app(\App\Policies\WalletPolicy::class)->view($user, $wallet), 404, 'Wallet account not found.');
        if (!$account->status || $account->status !== 'active' || $wallet->status !== 'active') throw ValidationException::withMessages(['wallet' => 'Wallet is not active.']);
        if (!$user->email_verified_at) throw ValidationException::withMessages(['email' => 'Email verification is required for payment operations.']);
        if (!$this->eligibility->capability((string) $user->country_code, $capability)) throw ValidationException::withMessages(['country_code' => 'This payment operation is not enabled for the account country.']);
        $kycEnabled = $this->eligibility->capability((string) $user->country_code, 'kyc_enabled');
        if ($kycEnabled && !KycCase::where('user_id', $user->id)->where('status', 'approved')->exists()) throw ValidationException::withMessages(['kyc' => 'Approved KYC is required for this payment operation.']);
        if (!Currency::where('code', $account->currency()->value('code'))->where('is_active', true)->exists()) throw ValidationException::withMessages(['currency' => 'Currency is not available.']);
    }
}
