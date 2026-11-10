<?php
namespace Tests\Feature;

use App\Domain\Ledger\FinancialCommand;
use App\Domain\Ledger\LedgerLine;
use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletCurrencyAccount;
use App\Models\WalletOwner;
use App\Services\ChartOfAccountsService;
use App\Services\FxQuoteService;
use App\Services\FxConversionService;
use App\Services\LedgerPostingService;
use Database\Seeders\FxSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class FxEngineTest extends TestCase
{
    private User $user;
    private WalletCurrencyAccount $usd;
    private WalletCurrencyAccount $eur;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
        Currency::create(['code'=>'USD','name'=>'US Dollar','symbol'=>'$','decimals'=>2,'display_order'=>1,'is_active'=>true]);
        Currency::create(['code'=>'EUR','name'=>'Euro','symbol'=>'€','decimals'=>2,'display_order'=>2,'is_active'=>true]);
        DB::table('countries')->insert(['code'=>'MM','name'=>'Myanmar','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('country_capabilities')->insert(['country_code'=>'MM','registration_enabled'=>true,'kyc_enabled'=>false,'wallet_enabled'=>true,'deposits_enabled'=>true,'withdrawals_enabled'=>false,'transfers_enabled'=>false,'fx_enabled'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $this->user=User::create(['name'=>'FX Tester','email'=>'fx@example.com','password'=>'password-password','country_code'=>'MM','phone_e164'=>'+95999999992','account_status'=>'active','user_type'=>'customer','email_verified_at'=>now()]);
        $owner=WalletOwner::create(['user_id'=>$this->user->id,'owner_type'=>'customer','status'=>'active']);
        $wallet=Wallet::create(['owner_id'=>$owner->id,'user_id'=>$this->user->id,'currency_id'=>Currency::where('code','USD')->value('id'),'label'=>'FX wallet','wallet_type'=>'customer','status'=>'active']);
        $this->usd=WalletCurrencyAccount::create(['wallet_id'=>$wallet->id,'currency_id'=>Currency::where('code','USD')->value('id'),'status'=>'active']);
        $this->eur=WalletCurrencyAccount::create(['wallet_id'=>$wallet->id,'currency_id'=>Currency::where('code','EUR')->value('id'),'status'=>'active']);
        $chart=app(ChartOfAccountsService::class); $usdLedger=$chart->provisionWalletAccount($this->usd); $chart->provisionWalletAccount($this->eur); $settlement=$chart->provisionSystemAccount('system.test.settlement.usd','Test settlement USD','asset','debit','USD');
        app(LedgerPostingService::class)->post(new FinancialCommand('sandbox_funding','fx-funding',[new LedgerLine($settlement->id,'USD','debit',100000),new LedgerLine($usdLedger->id,'USD','credit',100000)],checkAvailableFunds:false),$this->user->id,'fx-funding-key');
        app(FxSeeder::class)->run();
    }

    public function test_quote_is_server_calculated_with_spread_and_sandbox_flag(): void
    {
        $quote=app(FxQuoteService::class)->create($this->user,$this->usd,$this->eur,10000);
        $this->assertTrue($quote->sandbox_only);
        $this->assertSame('USD',$quote->source_currency_code);
        $this->assertSame(10000,(int)$quote->source_amount_minor);
        $this->assertSame('0.915400000000000000',(string)$quote->exchange_rate);
        $this->assertSame(9154,(int)$quote->estimated_target_amount_minor);
        $this->assertSame(50,(int)$quote->platform_fee_minor);
    }

    public function test_conversion_posts_two_balanced_currency_legs_and_replays_idempotently(): void
    {
        $quote=app(FxQuoteService::class)->create($this->user,$this->usd,$this->eur,10000);
        $this->actingAs($this->user,'web')->withHeader('Idempotency-Key','fx-execute-1');
        $first=app(FxConversionService::class)->execute($this->user,$quote,'fx-execute-1');
        $second=app(FxConversionService::class)->execute($this->user,$quote,'fx-execute-1');
        $this->assertSame($first->id,$second->id);
        $this->assertSame(89950,app(\App\Services\BalanceProjectionService::class)->get($this->usd)['available_minor']);
        $this->assertSame(9154,app(\App\Services\BalanceProjectionService::class)->get($this->eur)['posted_minor']);
        $this->assertCount(5,$first->legs);
    }

    public function test_cross_user_quote_access_is_rejected(): void
    {
        $quote=app(FxQuoteService::class)->create($this->user,$this->usd,$this->eur,1000);
        $other=User::create(['name'=>'Other','email'=>'other-fx@example.com','password'=>'password-password','country_code'=>'MM','account_status'=>'active','user_type'=>'customer']);
        $this->expectException(ValidationException::class);
        app(FxConversionService::class)->execute($other,$quote,'other-key');
    }

    public function test_insufficient_source_funds_roll_back_conversion(): void
    {
        $quote=app(FxQuoteService::class)->create($this->user,$this->usd,$this->eur,999999);
        $this->expectException(ValidationException::class);
        app(FxConversionService::class)->execute($this->user,$quote,'insufficient-key');
        $this->assertDatabaseMissing('fx_conversions',['quote_id'=>$quote->id]);
    }
}
