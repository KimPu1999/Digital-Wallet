<?php

namespace Tests\Feature;

use App\Domain\Ledger\FinancialCommand;
use App\Domain\Ledger\LedgerLine;
use App\Domain\Reporting\ReportExportService;
use App\Domain\Reporting\ReportQueryService;
use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\LedgerTransaction;
use App\Models\ReportRun;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletCurrencyAccount;
use App\Models\WalletOwner;
use App\Services\ChartOfAccountsService;
use App\Services\LedgerPostingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class FinancialReportingTest extends TestCase
{
    private User $user;
    private LedgerAccount $walletAccount;
    private LedgerAccount $settlementAccount;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
        $currency = Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimals' => 2, 'display_order' => 1, 'is_active' => true]);
        $this->user = User::create(['name' => 'Reports Tester', 'email' => 'reports@example.com', 'password' => 'password-password', 'country_code' => 'MM', 'phone_e164' => '+95999999998', 'account_status' => 'active', 'user_type' => 'customer']);
        $owner = WalletOwner::create(['user_id' => $this->user->id, 'owner_type' => 'customer', 'status' => 'active']);
        $wallet = Wallet::create(['owner_id' => $owner->id, 'user_id' => $this->user->id, 'currency_id' => $currency->id, 'label' => 'Reporting wallet', 'wallet_type' => 'customer', 'status' => 'active']);
        $account = WalletCurrencyAccount::create(['wallet_id' => $wallet->id, 'currency_id' => $currency->id, 'status' => 'active']);
        $this->walletAccount = app(ChartOfAccountsService::class)->provisionWalletAccount($account);
        $this->settlementAccount = app(ChartOfAccountsService::class)->provisionSystemAccount('report.settlement.usd', 'Report settlement USD', 'asset', 'debit', 'USD');
    }

    public function test_trial_balance_uses_posted_entries_and_reconciles_by_currency(): void
    {
        $this->postDeposit(1250, 'report-posted');
        LedgerTransaction::create(['reference' => 'report-pending', 'transaction_type' => 'pending', 'status' => 'pending']);
        $report = app(ReportQueryService::class)->trialBalance(['from' => now()->subDay()->toDateString(), 'to' => now()->addDay()->toDateString()]);
        $this->assertTrue($report['balanced_by_currency']['USD']['balanced']);
        $this->assertCount(2, $report['rows']);
        $this->assertSame(1250, collect($report['rows'])->sum('debit_minor'));
    }

    public function test_profit_loss_does_not_treat_payment_volume_as_revenue(): void
    {
        $revenue = app(ChartOfAccountsService::class)->provisionSystemAccount('report.fee.usd', 'Report fee revenue USD', 'revenue', 'credit', 'USD');
        app(LedgerPostingService::class)->post(new FinancialCommand('fee', 'report-fee', [new LedgerLine($this->settlementAccount->id, 'USD', 'debit', 25), new LedgerLine($revenue->id, 'USD', 'credit', 25)], checkAvailableFunds: false), $this->user->id);
        $report = app(ReportQueryService::class)->profitLoss(['from' => now()->subDay()->toDateString(), 'to' => now()->addDay()->toDateString()]);
        $this->assertSame(25, $report['by_currency']['USD']['revenue_minor']);
        $this->assertSame(25, $report['by_currency']['USD']['net_income_minor']);
    }

    public function test_balance_sheet_is_currency_separated(): void
    {
        $this->postDeposit(500, 'report-balance-sheet');
        $report = app(ReportQueryService::class)->balanceSheet(['to' => now()->addDay()->toDateString()]);
        $this->assertArrayHasKey('USD', $report['by_currency']);
        $this->assertTrue($report['balanced']);
    }

    public function test_report_period_is_bounded(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(ReportQueryService::class)->overview(['from' => '2020-01-01', 'to' => '2022-01-01']);
    }

    public function test_csv_export_neutralizes_formula_values_and_stays_private(): void
    {
        Storage::fake('local');
        $run = ReportRun::create(['status' => 'completed', 'scope' => 'platform', 'result_summary' => ['report_type' => 'test'], 'expires_at' => now()->addDay()]);
        $file = app(ReportExportService::class)->csv($run, [['reference' => '=HYPERLINK("https://unsafe")', 'amount_minor' => 10]], $this->user->id);
        Storage::disk('local')->assertExists($file->storage_key);
        $this->assertStringContainsString("'=HYPERLINK", Storage::disk('local')->get($file->storage_key));
    }

    private function postDeposit(int $amount, string $reference): void
    {
        app(LedgerPostingService::class)->post(new FinancialCommand('deposit', $reference, [new LedgerLine($this->settlementAccount->id, 'USD', 'debit', $amount), new LedgerLine($this->walletAccount->id, 'USD', 'credit', $amount)], checkAvailableFunds: false), $this->user->id, $reference.'-key');
    }
}
