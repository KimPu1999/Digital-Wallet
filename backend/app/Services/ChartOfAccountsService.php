<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\WalletCurrencyAccount;
use App\Models\BalanceProjection;
use Illuminate\Support\Facades\DB;

final class ChartOfAccountsService
{
    public function provisionWalletAccount(WalletCurrencyAccount $walletAccount): LedgerAccount
    {
        $currency = $walletAccount->currency()->firstOrFail();
        return DB::transaction(function () use ($walletAccount, $currency): LedgerAccount {
            $account = LedgerAccount::firstOrCreate(
                ['wallet_currency_account_id' => $walletAccount->id],
                ['currency_code' => $currency->code, 'code' => 'wallet.'.$walletAccount->id, 'name' => 'Wallet '.$currency->code, 'category' => 'liability', 'normal_side' => 'credit', 'status' => 'active']
            );
            BalanceProjection::firstOrCreate(['wallet_currency_account_id' => $walletAccount->id], ['currency_code' => $currency->code, 'posted_minor' => 0, 'held_minor' => 0, 'version' => 0]);
            return $account;
        });
    }

    public function provisionSystemAccount(string $code, string $name, string $category, string $normalSide, string $currencyCode, bool $restricted = false): LedgerAccount
    {
        Currency::where('code', strtoupper($currencyCode))->where('is_active', true)->firstOrFail();
        return LedgerAccount::firstOrCreate(['code' => $code], ['name' => $name, 'category' => $category, 'normal_side' => $normalSide, 'currency_code' => strtoupper($currencyCode), 'status' => 'active', 'restricted' => $restricted]);
    }
}
