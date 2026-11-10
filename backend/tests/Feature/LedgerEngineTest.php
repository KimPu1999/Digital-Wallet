<?php

namespace Tests\Feature;

use App\Domain\Ledger\FinancialCommand;
use App\Domain\Ledger\LedgerLine;
use App\Models\BalanceProjection;
use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletCurrencyAccount;
use App\Models\WalletOwner;
use App\Services\ChartOfAccountsService;
use App\Services\LedgerPostingService;
use App\Services\LedgerReversalService;
use App\Services\ReconciliationService;
use App\Services\WalletHoldService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

final class LedgerEngineTest extends TestCase
{
    private User $user;
    private WalletCurrencyAccount $walletAccount;
    private LedgerAccount $walletLedgerAccount;
    private LedgerAccount $settlementAccount;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
        $currency = Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimals' => 2, 'display_order' => 1, 'is_active' => true]);
        $this->user = User::create(['name' => 'Ledger Tester', 'email' => 'ledger@example.com', 'password' => 'password-password', 'country_code' => 'MM', 'phone_e164' => '+95999999991', 'account_status' => 'active', 'user_type' => 'customer']);
        $owner = WalletOwner::create(['user_id' => $this->user->id, 'owner_type' => 'customer', 'status' => 'active']);
        $wallet = Wallet::create(['owner_id' => $owner->id, 'user_id' => $this->user->id, 'currency_id' => $currency->id, 'label' => 'Test wallet', 'wallet_type' => 'customer', 'status' => 'active']);
        $this->walletAccount = WalletCurrencyAccount::create(['wallet_id' => $wallet->id, 'currency_id' => $currency->id, 'status' => 'active']);
        $this->walletLedgerAccount = app(ChartOfAccountsService::class)->provisionWalletAccount($this->walletAccount);
        $this->settlementAccount = app(ChartOfAccountsService::class)->provisionSystemAccount('system.settlement.usd', 'Settlement USD', 'asset', 'debit', 'USD');
    }

    public function test_balanced_journal_posts_atomically_and_updates_projection(): void
    {
        $transaction = $this->deposit(1000, 'deposit-1');
        $this->assertSame('posted', $transaction->status);
        $projection = BalanceProjection::where('wallet_currency_account_id', $this->walletAccount->id)->firstOrFail();
        $this->assertSame(1000, $projection->posted_minor);
        $this->assertSame(1000, $projection->posted_minor - $projection->held_minor);
        $this->assertSame(2, LedgerEntry::where('ledger_transaction_id', $transaction->id)->count());
    }

    public function test_unbalanced_and_currency_mismatch_journals_are_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(LedgerPostingService::class)->post(new FinancialCommand('invalid', 'invalid-1', [new LedgerLine($this->settlementAccount->id, 'USD', 'debit', 100), new LedgerLine($this->walletLedgerAccount->id, 'USD', 'credit', 90)]), $this->user->id);
    }

    public function test_currency_mismatch_is_rejected(): void
    {
        LedgerAccount::create(['code' => 'system.eur', 'name' => 'EUR system', 'category' => 'asset', 'normal_side' => 'debit', 'currency_code' => 'EUR', 'status' => 'active']);
        $this->expectException(ValidationException::class);
        app(LedgerPostingService::class)->post(new FinancialCommand('invalid', 'invalid-currency', [new LedgerLine($this->settlementAccount->id, 'EUR', 'debit', 100), new LedgerLine($this->walletLedgerAccount->id, 'USD', 'credit', 100)]), $this->user->id);
    }

    public function test_ledger_entries_are_immutable(): void
    {
        $entry = $this->deposit(100, 'immutable-1')->entries()->firstOrFail();
        $this->expectException(LogicException::class);
        $entry->update(['amount_minor' => 200]);
    }

    public function test_idempotency_replays_without_double_posting_and_rejects_mismatch(): void
    {
        $command = new FinancialCommand('deposit', 'idempotent-1', [new LedgerLine($this->settlementAccount->id, 'USD', 'debit', 100), new LedgerLine($this->walletLedgerAccount->id, 'USD', 'credit', 100)], checkAvailableFunds: false);
        $first = app(LedgerPostingService::class)->post($command, $this->user->id, 'same-key');
        $second = app(LedgerPostingService::class)->post($command, $this->user->id, 'same-key');
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, LedgerTransaction::where('reference', 'idempotent-1')->count());
        $this->expectException(ValidationException::class);
        app(LedgerPostingService::class)->post(new FinancialCommand('deposit', 'idempotent-2', [new LedgerLine($this->settlementAccount->id, 'USD', 'debit', 101), new LedgerLine($this->walletLedgerAccount->id, 'USD', 'credit', 101)], checkAvailableFunds: false), $this->user->id, 'same-key');
    }

    public function test_insufficient_funds_and_hold_lifecycle_are_enforced(): void
    {
        $this->deposit(1000, 'hold-funds');
        $hold = app(WalletHoldService::class)->create($this->user, $this->walletAccount, 700, 'auth-1', 'hold-key');
        $balance = app(\App\Services\BalanceProjectionService::class)->get($this->walletAccount);
        $this->assertSame(700, $balance['held_minor']);
        $this->assertSame(300, $balance['available_minor']);
        app(WalletHoldService::class)->release($this->user, $hold, 'release-key');
        $this->assertSame('released', $hold->refresh()->status);
        $this->assertSame(0, app(\App\Services\BalanceProjectionService::class)->get($this->walletAccount)['held_minor']);
    }

    public function test_hold_capture_posts_once_and_hold_expiry_releases_amount(): void
    {
        $this->deposit(1000, 'capture-funds');
        $hold = app(WalletHoldService::class)->create($this->user, $this->walletAccount, 600, 'capture-1', 'capture-hold-key');
        app(WalletHoldService::class)->capture($this->user, $hold, $this->settlementAccount->id, 'capture-key');
        $balance = app(\App\Services\BalanceProjectionService::class)->get($this->walletAccount);
        $this->assertSame('captured', $hold->refresh()->status);
        $this->assertSame(400, $balance['posted_minor']);
        $this->assertSame(0, $balance['held_minor']);

        $expiring = app(WalletHoldService::class)->create($this->user, $this->walletAccount, 100, 'expiry-1', 'expiry-key', now()->subMinute());
        $this->assertSame(1, app(WalletHoldService::class)->expire());
        $this->assertSame('expired', $expiring->refresh()->status);
    }

    public function test_spending_cannot_exceed_available_funds(): void
    {
        $this->deposit(100, 'small-funds');
        $this->expectException(ValidationException::class);
        app(LedgerPostingService::class)->post(new FinancialCommand('spend', 'overspend-1', [new LedgerLine($this->walletLedgerAccount->id, 'USD', 'debit', 101), new LedgerLine($this->settlementAccount->id, 'USD', 'credit', 101)]), $this->user->id);
    }

    public function test_reversal_is_compensating_and_cannot_be_duplicated(): void
    {
        $original = $this->deposit(500, 'reverse-me');
        $reversal = app(LedgerReversalService::class)->reverse($original, 'Test reversal', $this->user->id, 'reverse-key');
        $this->assertSame('posted', $reversal->status);
        $this->assertSame(0, BalanceProjection::where('wallet_currency_account_id', $this->walletAccount->id)->value('posted_minor'));
        $this->expectException(ValidationException::class);
        app(LedgerReversalService::class)->reverse($original, 'Duplicate', $this->user->id, 'reverse-key-2');
    }

    public function test_reconciliation_reports_matching_projection(): void
    {
        $this->deposit(250, 'reconcile-1');
        $run = app(ReconciliationService::class)->run($this->user->id);
        $this->assertContains($run->status, ['completed', 'completed_with_exceptions']);
        $this->assertSame(0, $run->summary['exceptions']);
    }

    public function test_projection_can_be_rebuilt_from_immutable_entries(): void
    {
        $this->deposit(250, 'rebuild-1');
        BalanceProjection::where('wallet_currency_account_id', $this->walletAccount->id)->update(['posted_minor' => 999]);
        $rebuilt = app(\App\Services\BalanceProjectionService::class)->rebuild($this->walletAccount);
        $this->assertSame(250, $rebuilt['posted_minor']);
    }

    public function test_customer_balance_api_is_wallet_scoped(): void
    {
        $this->actingAs($this->user, 'web');
        $this->getJson('/api/v1/wallets/'.$this->walletAccount->wallet_id.'/balance')->assertOk()->assertJsonPath('data.ledger_source_of_truth', true);
    }

    public function test_customer_cannot_access_admin_ledger_api(): void
    {
        $this->actingAs($this->user, 'web');
        $this->getJson('/api/v1/admin/ledger/accounts')->assertForbidden();
    }

    private function deposit(int $amount, string $reference): LedgerTransaction
    {
        return app(LedgerPostingService::class)->post(new FinancialCommand('deposit', $reference, [new LedgerLine($this->settlementAccount->id, 'USD', 'debit', $amount), new LedgerLine($this->walletLedgerAccount->id, 'USD', 'credit', $amount)], checkAvailableFunds: false), $this->user->id, $reference.'-key');
    }
}
