<?php

namespace Tests\Feature;

use App\Domain\Ledger\FinancialCommand;
use App\Domain\Ledger\LedgerLine;
use App\Domain\Payments\StripePaymentAdapter;
use App\Models\Currency;
use App\Models\PaymentIntent;
use App\Models\PaymentProvider;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletCurrencyAccount;
use App\Models\WalletOwner;
use App\Services\ChartOfAccountsService;
use App\Services\LedgerPostingService;
use App\Services\PaymentOrchestrator;
use App\Services\PaymentWebhookService;
use Database\Seeders\PaymentSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class PaymentEngineTest extends TestCase
{
    private User $user;
    private WalletCurrencyAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
        Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimals' => 2, 'display_order' => 1, 'is_active' => true]);
        DB::table('countries')->insert(['code' => 'MM', 'name' => 'Myanmar', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('country_capabilities')->insert(['country_code' => 'MM', 'registration_enabled' => true, 'kyc_enabled' => false, 'wallet_enabled' => true, 'deposits_enabled' => true, 'withdrawals_enabled' => true, 'transfers_enabled' => true, 'fx_enabled' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->user = User::create(['name' => 'Payment User', 'email' => 'payment@example.com', 'password' => 'password-password', 'country_code' => 'MM', 'account_status' => 'active', 'user_type' => 'customer', 'email_verified_at' => now()]);
        $this->user->forceFill(['email_verified_at' => now()])->save();
        $owner = WalletOwner::create(['user_id' => $this->user->id, 'owner_type' => 'customer', 'status' => 'active']);
        $wallet = Wallet::create(['owner_id' => $owner->id, 'user_id' => $this->user->id, 'currency_id' => Currency::value('id'), 'label' => 'Payment wallet', 'wallet_type' => 'customer', 'status' => 'active']);
        $this->account = WalletCurrencyAccount::create(['wallet_id' => $wallet->id, 'currency_id' => Currency::value('id'), 'status' => 'active']);
        app(ChartOfAccountsService::class)->provisionWalletAccount($this->account);
        (new PaymentSeeder())->run();
    }

    public function test_deposit_intent_requires_verified_webhook_and_credits_once(): void
    {
        $intent = app(PaymentOrchestrator::class)->createDeposit($this->user, $this->account, 2500, 'card', 'deposit-key-1');
        $this->assertSame('processing', $intent->status);
        $provider = PaymentProvider::where('code', 'sandbox-mock')->firstOrFail();
        $account = $provider->accounts()->firstOrFail();
        $payload = json_encode(['id' => 'evt-deposit-1', 'type' => 'payment.succeeded', 'status' => 'succeeded', 'intent_reference' => $intent->intent_reference, 'amount_minor' => 2500, 'currency_code' => 'USD', 'fee_minor' => 0, 'occurred_at' => now()->toIso8601String()], JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $headers = ['X-Mock-Timestamp' => $timestamp, 'X-Mock-Signature' => hash_hmac('sha256', $timestamp.'.'.$payload, 'sandbox-webhook-secret')];
        $first = app(PaymentWebhookService::class)->receive($provider, $account, $payload, $headers);
        $second = app(PaymentWebhookService::class)->receive($provider, $account, $payload, $headers);
        $this->assertFalse($first['duplicate']);
        $this->assertTrue($second['duplicate']);
        $this->assertSame('succeeded', $intent->refresh()->status);
        $this->assertSame(2500, (int) $this->account->projection()->value('posted_minor'));
        $this->assertSame(1, PaymentIntent::find($intent->id)->settlementItems()->count());
    }

    public function test_deposit_with_invalid_webhook_signature_is_rejected(): void
    {
        $intent = app(PaymentOrchestrator::class)->createDeposit($this->user, $this->account, 1000, 'card', 'deposit-key-2');
        $provider = PaymentProvider::where('code', 'sandbox-mock')->firstOrFail();
        $account = $provider->accounts()->firstOrFail();
        $payload = json_encode(['id' => 'evt-invalid', 'type' => 'payment.succeeded', 'status' => 'succeeded', 'intent_reference' => $intent->intent_reference, 'amount_minor' => 1000, 'currency_code' => 'USD'], JSON_THROW_ON_ERROR);
        $this->expectException(ValidationException::class);
        app(PaymentWebhookService::class)->receive($provider, $account, $payload, ['X-Mock-Timestamp' => (string) now()->timestamp, 'X-Mock-Signature' => 'invalid']);
    }

    public function test_withdrawal_reserves_and_captures_a_hold_only_after_verified_webhook(): void
    {
        $walletLedger = $this->account->ledgerAccount()->firstOrFail();
        $seed = app(ChartOfAccountsService::class)->provisionSystemAccount('system.payment.seed', 'Payment seed', 'asset', 'debit', 'USD', true);
        app(LedgerPostingService::class)->post(new FinancialCommand('sandbox_funding', 'payment-funding', [new LedgerLine($seed->id, 'USD', 'debit', 10000), new LedgerLine($walletLedger->id, 'USD', 'credit', 10000)], checkAvailableFunds: false), $this->user->id, 'payment-funding-key');
        $challenge = app(\App\Services\TransferStepUpService::class)->issue($this->user);
        $intent = app(PaymentOrchestrator::class)->createWithdrawal($this->user, $this->account, 4000, 'bank_transfer', 'withdraw-key-1', $challenge['challenge_id'], $challenge['sandbox_code']);
        $this->assertSame('processing', $intent->status);
        $this->assertSame(4000, (int) $this->account->projection()->value('held_minor'));
        $provider = PaymentProvider::where('code', 'sandbox-mock')->firstOrFail();
        $payload = json_encode(['id' => 'evt-withdraw-1', 'type' => 'payout.succeeded', 'status' => 'succeeded', 'intent_reference' => $intent->intent_reference, 'amount_minor' => 4000, 'currency_code' => 'USD'], JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        app(PaymentWebhookService::class)->receive($provider, $provider->accounts()->firstOrFail(), $payload, ['X-Mock-Timestamp' => $timestamp, 'X-Mock-Signature' => hash_hmac('sha256', $timestamp.'.'.$payload, 'sandbox-webhook-secret')]);
        $this->assertSame('succeeded', $intent->refresh()->status);
        $this->assertSame(0, (int) $this->account->projection()->value('held_minor'));
        $this->assertSame(6000, (int) $this->account->projection()->value('posted_minor'));
    }

    public function test_unknown_provider_outcome_does_not_post_or_retry(): void
    {
        $intent = app(PaymentOrchestrator::class)->createDeposit($this->user, $this->account, 1500, 'card', 'deposit-key-unknown', 'unknown');
        $this->assertSame('unknown', $intent->status);
        $this->assertSame(0, (int) $this->account->projection()->value('posted_minor'));
        $this->assertSame(1, $intent->attempts()->count());
    }

    public function test_unconfigured_provider_operation_is_explicitly_unsupported(): void
    {
        $this->expectException(\App\Domain\Payments\UnsupportedPaymentOperation::class);
        (new StripePaymentAdapter())->createDeposit(PaymentIntent::make(['operation' => 'deposit']), PaymentProvider::where('code', 'sandbox-mock')->firstOrFail()->accounts()->firstOrFail());
    }
}
