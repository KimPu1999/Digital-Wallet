<?php

namespace App\Services;

use App\Domain\Ledger\FinancialCommand;
use App\Domain\Ledger\LedgerLine;
use App\Models\CheckoutSession;
use App\Models\Merchant;
use App\Models\MerchantCheckoutSetting;
use App\Models\MerchantPaymentLink;
use App\Models\MerchantQrCode;
use App\Models\PaymentIntent;
use App\Models\User;
use App\Models\WalletCurrencyAccount;
use App\Models\WalletOwner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class MerchantCheckoutService
{
    public function __construct(private MerchantAccessService $access, private IdempotencyService $idempotency, private LedgerPostingService $posting, private ChartOfAccountsService $chart, private TransferStepUpService $stepUp, private RedirectUrlValidator $redirects) {}

    public function createLink(User $user, Merchant $merchant, array $data): array
    {
        $this->access->assert($user, $merchant, ['owner', 'manager', 'finance']);
        $this->assertAmountCurrency($merchant, (int) ($data['amount_minor'] ?? 0), strtoupper((string) $data['currency_code']));
        $raw = 'link_'.Str::random(48);
        $link = MerchantPaymentLink::create(['merchant_id' => $merchant->id, 'created_by' => $user->id, 'public_token_hash' => hash('sha512', $raw), 'amount_minor' => $data['amount_minor'] ?? null, 'currency_code' => strtoupper($data['currency_code']), 'description' => $data['description'] ?? null, 'status' => 'active', 'expires_at' => $data['expires_at'] ?? null, 'usage_limit' => $data['usage_limit'] ?? null, 'single_use' => (bool) ($data['single_use'] ?? false), 'metadata' => ['sandbox_only' => true]]);
        return ['link' => $link, 'public_token' => $raw];
    }

    public function createSession(User $user, Merchant $merchant, array $data, ?MerchantPaymentLink $link = null): array
    {
        $this->access->assert($user, $merchant, ['owner', 'manager', 'finance', 'developer']);
        if ($link) {
            if ((string) $link->merchant_id !== (string) $merchant->id || $link->status !== 'active' || ($link->expires_at && $link->expires_at->isPast()) || ($link->usage_limit !== null && $link->usage_count >= $link->usage_limit)) throw ValidationException::withMessages(['link' => 'Payment link is unavailable.']);
        }
        $amount = (int) ($data['amount_minor'] ?? $link?->amount_minor ?? 0);
        $currency = strtoupper((string) ($data['currency_code'] ?? $link?->currency_code ?? ''));
        $this->assertAmountCurrency($merchant, $amount, $currency);
        $settings = $merchant->hasOne(MerchantCheckoutSetting::class)->first();
        $allowedHosts = $settings?->allowed_redirect_hosts ?? [];
        $this->redirects->assertAllowed($data['success_url'] ?? $settings?->default_success_url, $allowedHosts);
        $this->redirects->assertAllowed($data['cancel_url'] ?? $settings?->default_cancel_url, $allowedHosts);
        $raw = 'checkout_'.Str::random(48);
        $session = CheckoutSession::create(['merchant_id' => $merchant->id, 'payment_link_id' => $link?->id, 'public_token_hash' => hash('sha512', $raw), 'order_reference' => (string) ($data['order_reference'] ?? 'ORDER-'.strtoupper(Str::random(12))), 'amount_minor' => $amount, 'currency_code' => $currency, 'status' => 'open', 'expires_at' => $data['expires_at'] ?? now()->addMinutes(30), 'success_url' => $data['success_url'] ?? $settings?->default_success_url, 'cancel_url' => $data['cancel_url'] ?? $settings?->default_cancel_url, 'allowed_payment_methods' => $data['allowed_payment_methods'] ?? ($settings?->allowed_payment_methods ?? ['wallet']), 'branding_snapshot' => $settings?->branding, 'metadata' => ['sandbox_only' => true, 'hosted_collection' => 'not_enabled']]);
        return ['session' => $session, 'public_token' => $raw];
    }

    public function payWallet(User $customer, CheckoutSession $session, WalletCurrencyAccount $source, string $idempotencyKey, string $challengeId, string $challengeCode): PaymentIntent
    {
        if (!$customer->email_verified_at) throw ValidationException::withMessages(['email' => 'Email verification is required.']);
        $result = $this->idempotency->execute('merchant.checkout.'.$session->id.'.'.$customer->id, $idempotencyKey, ['session_id' => $session->id, 'source_account_id' => $source->id, 'amount_minor' => $session->amount_minor], function () use ($customer, $session, $source, $idempotencyKey, $challengeId, $challengeCode): array {
            return DB::transaction(function () use ($customer, $session, $source, $idempotencyKey, $challengeId, $challengeCode): array {
                $locked = CheckoutSession::with('merchant')->whereKey($session->id)->lockForUpdate()->firstOrFail();
                if ($locked->status !== 'open' || $locked->isExpired()) {
                    if ($locked->status === 'open' && $locked->isExpired()) $locked->update(['status' => 'expired']);
                    throw ValidationException::withMessages(['checkout' => 'Checkout session is expired or already completed.']);
                }
                $this->stepUp->consume($customer, $challengeId, $challengeCode);
                $source = WalletCurrencyAccount::with(['wallet.owner', 'currency'])->whereKey($source->id)->lockForUpdate()->firstOrFail();
                $sourceWallet = $source->wallet()->with('owner')->firstOrFail();
                abort_unless(app(\App\Policies\WalletPolicy::class)->view($customer, $sourceWallet), 404, 'Wallet account not found.');
                if ($source->status !== 'active' || $source->currency->code !== $locked->currency_code) throw ValidationException::withMessages(['wallet' => 'Wallet is not eligible for this checkout.']);
                $merchantAccount = $this->merchantAccount($locked->merchant, $locked->currency_code);
                $sourceLedger = $this->chart->provisionWalletAccount($source);
                $merchantLedger = $this->chart->provisionWalletAccount($merchantAccount);
                $intent = PaymentIntent::create(['user_id' => $customer->id, 'merchant_id' => $locked->merchant_id, 'wallet_currency_account_id' => $source->id, 'operation' => 'merchant_payment', 'payment_method' => 'wallet', 'currency_code' => $locked->currency_code, 'amount_minor' => $locked->amount_minor, 'fee_minor' => 0, 'intent_reference' => 'MCH-PI-'.strtoupper(Str::random(18)), 'status' => 'processing', 'expires_at' => now()->addMinutes(15), 'sandbox_only' => true, 'metadata' => ['checkout_session_id' => $locked->id, 'sandbox_only' => true]]);
                $ledger = $this->posting->post(new FinancialCommand('merchant_wallet_checkout', 'MCH-PAY-'.$locked->id, [new LedgerLine($sourceLedger->id, $locked->currency_code, 'debit', $locked->amount_minor), new LedgerLine($merchantLedger->id, $locked->currency_code, 'credit', $locked->amount_minor)], 'Sandbox merchant wallet checkout', ['payment_intent_id' => $intent->id, 'checkout_session_id' => $locked->id, 'merchant_id' => $locked->merchant_id, 'sandbox_only' => true]), $customer->id, $idempotencyKey, 'merchant.checkout.ledger');
                $intent->update(['status' => 'succeeded', 'completed_at' => now(), 'settlement_reference' => 'WALLET-'.$ledger->id, 'metadata' => ['checkout_session_id' => $locked->id, 'ledger_transaction_id' => $ledger->id, 'sandbox_only' => true]]);
                $locked->update(['status' => 'paid', 'payment_intent_id' => $intent->id]);
                if ($locked->payment_link_id) {
                    $link = MerchantPaymentLink::whereKey($locked->payment_link_id)->lockForUpdate()->firstOrFail();
                    if ($link->single_use || ($link->usage_limit !== null && $link->usage_count + 1 >= $link->usage_limit)) $link->update(['usage_count' => $link->usage_count + 1, 'status' => 'inactive']);
                    else $link->increment('usage_count');
                }
                app(FinancialAuditLogger::class)->record('merchant.checkout.succeeded', PaymentIntent::class, $intent->id, ['merchant_id' => $locked->merchant_id, 'checkout_session_id' => $locked->id, 'sandbox_only' => true], actorId: $customer->id);
                return ['payment_intent_id' => $intent->id];
            });
        });
        return PaymentIntent::findOrFail($result->value['payment_intent_id']);
    }

    public function resolveSession(string $token): CheckoutSession
    {
        $session = CheckoutSession::with('merchant')->where('public_token_hash', hash('sha512', $token))->firstOrFail();
        if ($session->status === 'open' && $session->isExpired()) $session->update(['status' => 'expired']);
        return $session->refresh();
    }

    public function createQr(User $user, Merchant $merchant, array $data): array
    {
        $this->access->assert($user, $merchant, ['owner', 'manager', 'finance']);
        $type = $data['qr_type'] ?? 'static';
        if (!in_array($type, ['static', 'dynamic'], true)) throw ValidationException::withMessages(['qr_type' => 'QR type is invalid.']);
        $session = null;
        $sessionToken = null;
        if ($type === 'dynamic') {
            $sessionResult = $this->createSession($user, $merchant, $data);
            $session = $sessionResult['session'];
            $sessionToken = $sessionResult['public_token'];
        }
        $raw = 'qr_'.Str::random(48);
        $qr = MerchantQrCode::create(['merchant_id' => $merchant->id, 'checkout_session_id' => $session?->id, 'qr_reference' => 'QR-'.strtoupper(Str::random(18)), 'token_hash' => hash('sha512', $raw), 'qr_type' => $type, 'amount_minor' => $data['amount_minor'] ?? null, 'currency_code' => isset($data['currency_code']) ? strtoupper($data['currency_code']) : null, 'status' => 'active', 'expires_at' => $data['expires_at'] ?? null, 'payload' => ['sandbox_only' => true, 'reference_type' => 'opaque']]);
        return ['qr' => $qr, 'public_token' => $raw, 'checkout_token' => $sessionToken];
    }

    public function resolveQr(string $token): MerchantQrCode
    {
        $qr = MerchantQrCode::with(['merchant', 'checkoutSession'])->where('token_hash', hash('sha512', $token))->firstOrFail();
        if ($qr->status !== 'active' || ($qr->expires_at && $qr->expires_at->isPast())) throw ValidationException::withMessages(['qr' => 'QR reference is expired or disabled.']);
        return $qr;
    }

    private function merchantAccount(Merchant $merchant, string $currencyCode): WalletCurrencyAccount
    {
        $owner = WalletOwner::where('merchant_id', $merchant->id)->where('owner_type', 'merchant')->where('status', 'active')->firstOrFail();
        $account = WalletCurrencyAccount::with('currency')->whereHas('wallet', fn ($query) => $query->where('owner_id', $owner->id)->where('wallet_type', 'merchant'))->whereHas('currency', fn ($query) => $query->where('code', $currencyCode))->where('status', 'active')->first();
        if (!$account) throw ValidationException::withMessages(['currency' => 'Merchant has no active wallet account for this currency.']);
        return $account;
    }

    private function assertAmountCurrency(Merchant $merchant, int $amount, string $currency): void
    {
        if ($amount <= 0 || $amount > PHP_INT_MAX) throw ValidationException::withMessages(['amount_minor' => 'Amount must be a positive minor-unit integer.']);
        if (!\App\Models\Currency::where('code', $currency)->where('is_active', true)->exists()) throw ValidationException::withMessages(['currency_code' => 'Currency is unavailable.']);
        $settings = $merchant->hasOne(MerchantCheckoutSetting::class)->first();
        if ($settings?->allowed_currencies && !in_array($currency, $settings->allowed_currencies, true)) throw ValidationException::withMessages(['currency_code' => 'Currency is not enabled for this merchant.']);
    }
}
