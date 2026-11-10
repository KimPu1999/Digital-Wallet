<?php

namespace App\Services;

use App\Models\BalanceProjection;
use App\Models\LedgerAccount;
use App\Models\WalletCurrencyAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class BalanceProjectionService
{
    public function get(WalletCurrencyAccount $walletAccount): array
    {
        $projection = BalanceProjection::firstOrCreate(['wallet_currency_account_id' => $walletAccount->id], ['currency_code' => $walletAccount->currency()->value('code'), 'posted_minor' => 0, 'held_minor' => 0, 'version' => 0]);
        return $this->format($projection);
    }

    public function format(BalanceProjection $projection): array
    {
        $available = $projection->posted_minor - $projection->held_minor;
        return ['currency_code' => $projection->currency_code, 'posted_minor' => $projection->posted_minor, 'held_minor' => $projection->held_minor, 'available_minor' => $available, 'pending_minor' => 0, 'projection_version' => $projection->version, 'source_of_truth' => 'double-entry ledger'];
    }

    public function applyLedgerDelta(WalletCurrencyAccount $walletAccount, LedgerAccount $account, string $side, int $amount, ?string $postedAt = null): void
    {
        $projection = BalanceProjection::where('wallet_currency_account_id', $walletAccount->id)->lockForUpdate()->firstOrFail();
        $delta = $side === $account->normal_side ? $amount : -$amount;
        $next = $projection->posted_minor + $delta;
        if ($next < 0 && $account->normal_side === 'credit') throw ValidationException::withMessages(['balance' => 'Insufficient available funds.']);
        $projection->update(['posted_minor' => $next, 'version' => $projection->version + 1, 'last_posted_at' => $postedAt ?? now()]);
    }

    public function rebuild(WalletCurrencyAccount $walletAccount): array
    {
        return DB::transaction(function () use ($walletAccount): array {
            $account = LedgerAccount::where('wallet_currency_account_id', $walletAccount->id)->firstOrFail();
            $debit = (int) $account->entries()->where('currency_code', $account->currency_code)->where('side', 'debit')->sum('amount_minor');
            $credit = (int) $account->entries()->where('currency_code', $account->currency_code)->where('side', 'credit')->sum('amount_minor');
            $posted = $account->normal_side === 'credit' ? $credit - $debit : $debit - $credit;
            $projection = BalanceProjection::where('wallet_currency_account_id', $walletAccount->id)->lockForUpdate()->firstOrFail();
            if ($posted < 0 && $account->normal_side === 'credit') throw ValidationException::withMessages(['balance' => 'Rebuild found a negative wallet liability balance.']);
            $projection->update(['posted_minor' => $posted, 'version' => $projection->version + 1, 'last_posted_at' => now()]);
            return $this->format($projection->refresh());
        });
    }
}
