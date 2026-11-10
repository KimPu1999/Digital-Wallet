<?php
namespace Tests\Feature;

use App\Domain\Ledger\FinancialCommand;
use App\Domain\Ledger\LedgerLine;
use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletCurrencyAccount;
use App\Models\WalletOwner;
use App\Services\BeneficiaryService;
use App\Services\ChartOfAccountsService;
use App\Services\LedgerPostingService;
use App\Services\TransferQuoteService;
use App\Services\TransferService;
use App\Services\TransferStepUpService;
use Database\Seeders\TransferSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class TransferEngineTest extends TestCase
{
    private User $sender;
    private WalletCurrencyAccount $source;
    private WalletCurrencyAccount $recipient;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force'=>true]);
        Currency::create(['code'=>'USD','name'=>'US Dollar','symbol'=>'$','decimals'=>2,'display_order'=>1,'is_active'=>true]);
        DB::table('countries')->insert(['code'=>'MM','name'=>'Myanmar','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('country_capabilities')->insert(['country_code'=>'MM','registration_enabled'=>true,'kyc_enabled'=>false,'wallet_enabled'=>true,'deposits_enabled'=>true,'withdrawals_enabled'=>false,'transfers_enabled'=>true,'fx_enabled'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $this->sender=User::create(['name'=>'Sender','email'=>'sender@example.com','password'=>'password-password','country_code'=>'MM','account_status'=>'active','user_type'=>'customer','email_verified_at'=>now()]);
        $recipient=User::create(['name'=>'Recipient','email'=>'recipient@example.com','password'=>'password-password','country_code'=>'MM','account_status'=>'active','user_type'=>'customer','email_verified_at'=>now()]);
        $this->source=$this->walletAccount($this->sender,'Source wallet');
        $this->recipient=$this->walletAccount($recipient,'Recipient wallet');
        $chart=app(ChartOfAccountsService::class); $sourceLedger=$chart->provisionWalletAccount($this->source); $chart->provisionWalletAccount($this->recipient); $settlement=$chart->provisionSystemAccount('system.test.transfer.seed','Transfer seed','asset','debit','USD');
        app(LedgerPostingService::class)->post(new FinancialCommand('sandbox_funding','transfer-funding',[new LedgerLine($settlement->id,'USD','debit',100000),new LedgerLine($sourceLedger->id,'USD','credit',100000)],checkAvailableFunds:false),$this->sender->id,'transfer-funding-key');
        (new TransferSeeder())->run();
    }

    public function test_beneficiary_details_are_encrypted_and_masked(): void
    {
        $beneficiary=app(BeneficiaryService::class)->create($this->sender,['display_name'=>'Recipient wallet','beneficiary_type'=>'internal_wallet','country_code'=>'MM','currency_code'=>'USD','account_details'=>['wallet_account_id'=>$this->recipient->id]]);
        $account=$beneficiary->accounts->firstOrFail();
        $this->assertNotSame(json_encode(['wallet_account_id'=>$this->recipient->id]),$account->account_details_encrypted);
        $this->assertSame(4,strlen((string)$account->account_last4));
    }

    public function test_internal_transfer_requires_step_up_and_posts_atomically(): void
    {
        $beneficiary=app(BeneficiaryService::class)->create($this->sender,['display_name'=>'Recipient wallet','beneficiary_type'=>'internal_wallet','country_code'=>'MM','currency_code'=>'USD','account_details'=>['wallet_account_id'=>$this->recipient->id]]);
        $account=$beneficiary->accounts->firstOrFail();
        $quote=app(TransferQuoteService::class)->create($this->sender,$this->source,$beneficiary,$account,10000);
        $challenge=app(TransferStepUpService::class)->issue($this->sender);
        $this->actingAs($this->sender,'web')->withHeader('Idempotency-Key','transfer-execution-1');
        $transfer=app(TransferService::class)->execute($this->sender,$quote,'transfer-execution-1',$challenge['challenge_id'],$challenge['sandbox_code']);
        $this->assertSame('completed',$transfer->status);
        $this->assertSame(90000,app(\App\Services\BalanceProjectionService::class)->get($this->source)['available_minor']);
        $this->assertSame(10000,app(\App\Services\BalanceProjectionService::class)->get($this->recipient)['posted_minor']);
        $this->assertNotNull($transfer->receipt);
    }

    public function test_step_up_challenge_cannot_be_replayed(): void
    {
        $challenge=app(TransferStepUpService::class)->issue($this->sender);
        app(TransferStepUpService::class)->consume($this->sender,$challenge['challenge_id'],$challenge['sandbox_code']);
        $this->expectException(ValidationException::class);
        app(TransferStepUpService::class)->consume($this->sender,$challenge['challenge_id'],$challenge['sandbox_code']);
    }

    public function test_expired_quote_is_rejected(): void
    {
        $beneficiary=app(BeneficiaryService::class)->create($this->sender,['display_name'=>'Recipient wallet','beneficiary_type'=>'internal_wallet','currency_code'=>'USD','account_details'=>['wallet_account_id'=>$this->recipient->id]]);
        $account=$beneficiary->accounts->firstOrFail();
        $quote=app(TransferQuoteService::class)->create($this->sender,$this->source,$beneficiary,$account,1000);
        $quote->update(['expires_at'=>now()->subSecond()]);
        $challenge=app(TransferStepUpService::class)->issue($this->sender);
        $this->expectException(ValidationException::class);
        app(TransferService::class)->execute($this->sender,$quote,'expired-transfer',$challenge['challenge_id'],$challenge['sandbox_code']);
    }

    private function walletAccount(User $user, string $label): WalletCurrencyAccount
    {
        $owner=WalletOwner::create(['user_id'=>$user->id,'owner_type'=>'customer','status'=>'active']);
        $wallet=Wallet::create(['owner_id'=>$owner->id,'user_id'=>$user->id,'currency_id'=>Currency::where('code','USD')->value('id'),'label'=>$label,'wallet_type'=>'customer','status'=>'active']);
        return WalletCurrencyAccount::create(['wallet_id'=>$wallet->id,'currency_id'=>Currency::where('code','USD')->value('id'),'status'=>'active']);
    }
}
