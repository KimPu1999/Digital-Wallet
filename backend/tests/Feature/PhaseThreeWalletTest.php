<?php
namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Policies\WalletPolicy;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PhaseThreeWalletTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force'=>true]);
        DB::table('countries')->insert(['code'=>'MM','name'=>'Myanmar','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('country_capabilities')->insert(['country_code'=>'MM','registration_enabled'=>true,'kyc_enabled'=>false,'wallet_enabled'=>true,'deposits_enabled'=>false,'withdrawals_enabled'=>false,'transfers_enabled'=>false,'fx_enabled'=>false,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('currencies')->insert(['code'=>'USD','name'=>'US Dollar','symbol'=>'$','decimals'=>2,'display_order'=>1,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
    }

    public function test_customer_can_create_one_wallet_and_currency_account(): void
    {
        $user=$this->verifiedCustomer(); $this->actingAs($user,'web');
        $response=$this->postJson('/api/v1/wallets',['currency_code'=>'USD','nickname'=>'Everyday']);
        $response->assertCreated()->assertJsonPath('data.financial_data.available_balance',null);
        $this->assertDatabaseHas('wallet_currency_accounts',['currency_id'=>1,'status'=>'active']);
    }

    public function test_duplicate_currency_account_is_rejected(): void
    {
        $user=$this->verifiedCustomer(); $this->actingAs($user,'web'); $this->postJson('/api/v1/wallets',['currency_code'=>'USD']);
        $this->postJson('/api/v1/wallets',['currency_code'=>'USD'])->assertUnprocessable();
    }

    public function test_unsupported_currency_is_rejected(): void
    {
        $this->actingAs($this->verifiedCustomer(),'web'); $this->postJson('/api/v1/wallets',['currency_code'=>'EUR'])->assertUnprocessable();
    }

    public function test_wallet_is_not_visible_to_another_customer(): void
    {
        $owner=$this->verifiedCustomer(); $this->actingAs($owner,'web'); $id=$this->postJson('/api/v1/wallets',['currency_code'=>'USD'])->json('data.id');
        $other=$this->verifiedCustomer('other@example.com','+95912345679'); $this->assertFalse(app(WalletPolicy::class)->view($other,Wallet::findOrFail($id)));
    }

    public function test_ordinary_customer_cannot_access_admin_wallet_directory(): void
    {
        $this->actingAs($this->verifiedCustomer(),'web'); $this->getJson('/api/v1/admin/wallets')->assertForbidden();
    }

    private function verifiedCustomer(string $email='customer@example.com', string $phone='+95912345678'): User
    {
        $user=User::create(['name'=>'Customer','email'=>$email,'password'=>'password-password','country_code'=>'MM','phone_e164'=>$phone,'account_status'=>'active','user_type'=>'customer']);
        $user->forceFill(['email_verified_at'=>now()])->save(); return $user;
    }
}
