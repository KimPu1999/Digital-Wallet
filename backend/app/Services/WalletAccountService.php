<?php
namespace App\Services;

use App\Models\Currency;
use App\Models\KycCase;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletCurrencyAccount;
use App\Models\WalletOwner;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class WalletAccountService
{
    public function __construct(private CountryEligibilityService $eligibility, private AuditLogger $audit) {}

    public function create(User $user, string $currencyCode, ?string $nickname = null): Wallet
    {
        $currency = Currency::where('code', strtoupper($currencyCode))->where('is_active', true)->first();
        if (!$currency) throw ValidationException::withMessages(['currency_code' => 'This currency is not available.']);
        if (!$user->hasVerifiedEmail()) throw ValidationException::withMessages(['account' => 'Email verification is required before creating a wallet.']);
        if (!$user->country_code || !$this->eligibility->capability($user->country_code, 'wallet_enabled')) throw ValidationException::withMessages(['country_code' => 'Wallet accounts are not available in your country.']);
        if ($this->eligibility->capability($user->country_code, 'kyc_enabled') && !KycCase::where('user_id',$user->id)->where('status','approved')->exists()) throw ValidationException::withMessages(['kyc' => 'Approved KYC is required before creating a wallet.']);

        return DB::transaction(function () use ($user, $currency, $nickname): Wallet {
            User::whereKey($user->id)->lockForUpdate()->first();
            [$ownerType, $merchantId] = $this->resolveOwnerContext($user);
            $owner = WalletOwner::firstOrCreate(['user_id'=>$user->id,'owner_type'=>$ownerType,'merchant_id'=>$merchantId],['status'=>'active']);
            $wallet = $owner->wallets()->with(['owner','currencyAccounts.currency','preferences.preferredCurrency'])->lockForUpdate()->first();
            if ($wallet) {
                if ($wallet->currencyAccounts()->where('currency_id', $currency->id)->exists()) {
                    throw ValidationException::withMessages(['currency_code' => 'This currency account already exists.']);
                }
                $wallet->currencyAccounts()->create(['currency_id'=>$currency->id,'status'=>'active']);
                $this->audit->record('wallet.currency_account_created',request(),['wallet_id'=>$wallet->id,'currency_code'=>$currency->code]);
                return $wallet->load(['owner','currencyAccounts.currency','preferences.preferredCurrency']);
            }
            try { $wallet = Wallet::create(['owner_id'=>$owner->id,'user_id'=>$user->id,'currency_id'=>$currency->id,'label'=>$ownerType === 'merchant' ? 'Business wallet' : 'Personal wallet','nickname'=>$nickname,'wallet_type'=>$ownerType,'status'=>'active']); } catch (QueryException $e) { throw ValidationException::withMessages(['wallet'=>'Unable to create this wallet account.']); }
            $wallet->statusHistory()->create(['actor_id'=>$user->id,'to_status'=>'active','reason'=>'wallet_created']);
            $wallet->currencyAccounts()->create(['currency_id'=>$currency->id,'status'=>'active']);
            $wallet->preferences()->create(['preferred_currency_id'=>$currency->id,'locale'=>'en']);
            $this->audit->record('wallet.created',request(),['wallet_id'=>$wallet->id,'owner_type'=>$ownerType,'currency_code'=>$currency->code]);
            return $wallet->load(['owner','currencyAccounts.currency','preferences.preferredCurrency']);
        });
    }

    public function addCurrency(User $user, Wallet $wallet, string $currencyCode): WalletCurrencyAccount
    {
        $this->assertOwner($user, $wallet); $currency=Currency::where('code',strtoupper($currencyCode))->where('is_active',true)->first(); if(!$currency) throw ValidationException::withMessages(['currency_code'=>'This currency is not available.']);
        try { $account=$wallet->currencyAccounts()->create(['currency_id'=>$currency->id,'status'=>'active']); } catch(QueryException $e) { throw ValidationException::withMessages(['currency_code'=>'This currency account already exists.']); }
        $this->audit->record('wallet.currency_account_created',request(),['wallet_id'=>$wallet->id,'currency_code'=>$currency->code]); return $account->load('currency');
    }

    public function assertOwner(User $user, Wallet $wallet): void { abort_unless(app(\App\Policies\WalletPolicy::class)->view($user,$wallet->load('owner')), 404, 'Wallet not found.'); }

    private function resolveOwnerContext(User $user): array { if($user->user_type !== 'merchant') return ['customer',null]; $membership=DB::table('merchant_memberships')->where('user_id',$user->id)->orderBy('created_at')->first(); abort_unless($membership,403,'Merchant membership is required.'); return ['merchant',$membership->merchant_id]; }
}
