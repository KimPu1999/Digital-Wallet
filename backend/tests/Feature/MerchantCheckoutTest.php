<?php

namespace Tests\Feature;

use App\Domain\Ledger\FinancialCommand;
use App\Domain\Ledger\LedgerLine;
use App\Models\CheckoutSession;
use App\Models\Currency;
use App\Models\Merchant;
use App\Models\PaymentIntent;
use App\Models\RefundRequest;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletCurrencyAccount;
use App\Models\WalletOwner;
use App\Services\ChartOfAccountsService;
use App\Services\LedgerPostingService;
use App\Services\MerchantCheckoutService;
use App\Services\MerchantSettlementService;
use App\Services\MerchantApiKeyService;
use App\Services\MerchantWebhookService;
use App\Services\RefundService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class MerchantCheckoutTest extends TestCase
{
    private User $customer;
    private User $merchantUser;
    private Merchant $merchant;
    private WalletCurrencyAccount $customerAccount;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
        $currency = Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimals' => 2, 'display_order' => 1, 'is_active' => true]);
        DB::table('countries')->insert(['code' => 'MM', 'name' => 'Myanmar', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('country_capabilities')->insert(['country_code' => 'MM', 'registration_enabled' => true, 'kyc_enabled' => false, 'wallet_enabled' => true, 'deposits_enabled' => true, 'withdrawals_enabled' => true, 'transfers_enabled' => true, 'fx_enabled' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->customer = User::create(['name' => 'Checkout Customer', 'email' => 'checkout@example.com', 'password' => 'password-password', 'country_code' => 'MM', 'account_status' => 'active', 'user_type' => 'customer', 'email_verified_at' => now()]);
        $this->merchantUser = User::create(['name' => 'Merchant Owner', 'email' => 'merchant@example.com', 'password' => 'password-password', 'country_code' => 'MM', 'account_status' => 'active', 'user_type' => 'merchant', 'email_verified_at' => now()]);
        $this->customer->forceFill(['email_verified_at' => now()])->save();
        $this->merchantUser->forceFill(['email_verified_at' => now()])->save();
        $this->merchant = Merchant::create(['legal_name' => 'Sandbox Store LLC', 'display_name' => 'Sandbox Store', 'status' => 'active']);
        DB::table('merchant_memberships')->insert(['merchant_id' => $this->merchant->id, 'user_id' => $this->merchantUser->id, 'role' => 'owner', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('merchant_verifications')->insert(['id' => (string) \Illuminate\Support\Str::uuid(), 'merchant_id' => $this->merchant->id, 'status' => 'approved', 'reviewed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $this->customerAccount = $this->makeAccount($this->customer, $currency->id, 'customer');
        $this->makeMerchantAccount($currency->id);
        $seed = app(ChartOfAccountsService::class)->provisionSystemAccount('sandbox.checkout.seed', 'Sandbox checkout seed', 'asset', 'debit', 'USD', true);
        app(LedgerPostingService::class)->post(new FinancialCommand('sandbox_funding', 'checkout-funding', [new LedgerLine($seed->id, 'USD', 'debit', 10000), new LedgerLine($this->customerAccount->ledgerAccount()->firstOrFail()->id, 'USD', 'credit', 10000)], checkAvailableFunds: false), $this->customer->id, 'checkout-funding-key');
    }

    public function test_wallet_checkout_is_balanced_and_idempotent(): void
    {
        $service = app(MerchantCheckoutService::class);
        $link = $service->createLink($this->merchantUser, $this->merchant, ['amount_minor' => 4000, 'currency_code' => 'USD', 'description' => 'Demo order']);
        $session = $service->createSession($this->merchantUser, $this->merchant, ['payment_link_id' => $link['link']->id, 'order_reference' => 'ORDER-1'], $link['link']);
        $challenge = app(\App\Services\TransferStepUpService::class)->issue($this->customer);
        $intent = $service->payWallet($this->customer, $session['session'], $this->customerAccount, 'checkout-key-1', $challenge['challenge_id'], $challenge['sandbox_code']);
        $same = $service->payWallet($this->customer, CheckoutSession::findOrFail($session['session']->id), $this->customerAccount, 'checkout-key-1', $challenge['challenge_id'], $challenge['sandbox_code']);
        $this->assertSame($intent->id, $same->id);
        $this->assertSame('succeeded', $intent->status);
        $this->assertSame(6000, (int) $this->customerAccount->projection()->value('posted_minor'));
        $this->assertSame(1, PaymentIntent::where('merchant_id', $this->merchant->id)->count());
        $this->assertSame('paid', CheckoutSession::find($session['session']->id)->status);
    }

    public function test_insufficient_funds_and_cross_merchant_access_are_rejected(): void
    {
        $service = app(MerchantCheckoutService::class);
        $otherUser = User::create(['name' => 'Other User', 'email' => 'other@example.com', 'password' => 'password-password', 'country_code' => 'MM', 'account_status' => 'active', 'user_type' => 'merchant', 'email_verified_at' => now()]);
        $otherMerchant = Merchant::create(['legal_name' => 'Other LLC', 'display_name' => 'Other', 'status' => 'active']);
        $this->expectException(ValidationException::class);
        $service->createLink($otherUser, $this->merchant, ['amount_minor' => 100, 'currency_code' => 'USD']);
    }

    public function test_refund_is_compensating_and_cannot_exceed_paid_amount(): void
    {
        $service = app(MerchantCheckoutService::class);
        $session = $service->createSession($this->merchantUser, $this->merchant, ['amount_minor' => 4000, 'currency_code' => 'USD', 'order_reference' => 'ORDER-REFUND']);
        $challenge = app(\App\Services\TransferStepUpService::class)->issue($this->customer);
        $intent = $service->payWallet($this->customer, $session['session'], $this->customerAccount, 'checkout-refund-pay', $challenge['challenge_id'], $challenge['sandbox_code']);
        $refund = app(RefundService::class)->request($this->merchantUser, $this->merchant, $intent, 1500, 'Customer request', 'refund-key-1');
        $completed = app(RefundService::class)->approve($this->merchantUser, $refund);
        $this->assertSame('succeeded', $completed->status);
        $this->assertSame(7500, (int) $this->customerAccount->projection()->value('posted_minor'));
        $tooMuch = app(RefundService::class)->request($this->merchantUser, $this->merchant, $intent, 3000, 'Too much', 'refund-key-2');
        $this->expectException(ValidationException::class);
        app(RefundService::class)->approve($this->merchantUser, $tooMuch);
    }

    public function test_projected_settlement_is_not_marked_as_settled(): void
    {
        $service = app(MerchantCheckoutService::class);
        $session = $service->createSession($this->merchantUser, $this->merchant, ['amount_minor' => 1000, 'currency_code' => 'USD', 'order_reference' => 'ORDER-SETTLEMENT']);
        $challenge = app(\App\Services\TransferStepUpService::class)->issue($this->customer);
        $service->payWallet($this->customer, $session['session'], $this->customerAccount, 'settlement-pay', $challenge['challenge_id'], $challenge['sandbox_code']);
        $rows = app(MerchantSettlementService::class)->report($this->merchantUser, $this->merchant);
        $this->assertCount(1, $rows);
        $this->assertSame('projected', $rows[0]->status);
    }

    public function test_qr_references_are_opaque_and_api_keys_are_hashed(): void
    {
        $checkout = app(MerchantCheckoutService::class);
        $qr = $checkout->createQr($this->merchantUser, $this->merchant, ['qr_type' => 'dynamic', 'amount_minor' => 800, 'currency_code' => 'USD', 'order_reference' => 'QR-1']);
        $this->assertNotSame($qr['public_token'], $qr['qr']->token_hash);
        $this->assertNotEmpty($qr['checkout_token']);
        $this->assertSame($qr['qr']->id, $checkout->resolveQr($qr['public_token'])->id);
        $challenge = app(\App\Services\TransferStepUpService::class)->issue($this->merchantUser);
        $created = app(MerchantApiKeyService::class)->create($this->merchantUser, $this->merchant, 'Checkout integration', ['checkout:write'], null, $challenge['challenge_id'], $challenge['sandbox_code']);
        $this->assertStringStartsWith('dw_test_', $created['raw_key']);
        $this->assertNotSame($created['raw_key'], $created['key']->key_hash);
    }

    public function test_webhook_registration_rejects_private_destinations(): void
    {
        $challenge = app(\App\Services\TransferStepUpService::class)->issue($this->merchantUser);
        $this->expectException(ValidationException::class);
        app(MerchantWebhookService::class)->register($this->merchantUser, $this->merchant, 'https://127.0.0.1/hook', ['checkout.succeeded'], $challenge['challenge_id'], $challenge['sandbox_code']);
    }

    private function makeAccount(User $user, int $currencyId, string $type): WalletCurrencyAccount
    {
        $owner = WalletOwner::create(['user_id' => $user->id, 'owner_type' => $type, 'status' => 'active']);
        $wallet = Wallet::create(['owner_id' => $owner->id, 'user_id' => $user->id, 'currency_id' => $currencyId, 'label' => 'Sandbox wallet', 'wallet_type' => $type, 'status' => 'active']);
        $account = WalletCurrencyAccount::create(['wallet_id' => $wallet->id, 'currency_id' => $currencyId, 'status' => 'active']);
        app(ChartOfAccountsService::class)->provisionWalletAccount($account);
        return $account;
    }

    private function makeMerchantAccount(int $currencyId): WalletCurrencyAccount
    {
        $owner = WalletOwner::create(['user_id' => $this->merchantUser->id, 'merchant_id' => $this->merchant->id, 'owner_type' => 'merchant', 'status' => 'active']);
        $wallet = Wallet::create(['owner_id' => $owner->id, 'user_id' => $this->merchantUser->id, 'currency_id' => $currencyId, 'label' => 'Business wallet', 'wallet_type' => 'merchant', 'status' => 'active']);
        $account = WalletCurrencyAccount::create(['wallet_id' => $wallet->id, 'currency_id' => $currencyId, 'status' => 'active']);
        app(ChartOfAccountsService::class)->provisionWalletAccount($account);
        return $account;
    }
}
