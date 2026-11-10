<?php

namespace App\Services;

use App\Models\BalanceProjection;
use App\Models\LedgerAccount;
use App\Models\LedgerTransaction;
use App\Models\ReconciliationItem;
use App\Models\ReconciliationRun;
use App\Models\WalletHold;
use Illuminate\Support\Facades\DB;

final class ReconciliationService
{
    public function run(?string $actorId = null): ReconciliationRun
    {
        return DB::transaction(function () use ($actorId): ReconciliationRun {
            $run = ReconciliationRun::create(['run_type' => 'ledger_integrity', 'status' => 'running', 'started_by' => $actorId, 'started_at' => now()]);
            $exceptions = 0;
            foreach (LedgerTransaction::where('status', 'posted')->with('entries')->cursor() as $transaction) {
                $groups = $transaction->entries->groupBy('currency_code');
                foreach ($groups as $currency => $entries) {
                    $debit = $entries->where('side', 'debit')->sum('amount_minor');
                    $credit = $entries->where('side', 'credit')->sum('amount_minor');
                    if ($debit !== $credit) { $exceptions++; $this->item($run, 'journal_balance', LedgerTransaction::class, $transaction->id, $debit === $credit ? 'matched' : 'exception', $debit, $credit, $debit - $credit, ['currency_code' => $currency]); }
                }
            }
            foreach (BalanceProjection::with('walletCurrencyAccount')->cursor() as $projection) {
                $account = LedgerAccount::where('wallet_currency_account_id', $projection->wallet_currency_account_id)->first();
                if (!$account) { $exceptions++; $this->item($run, 'missing_ledger_account', BalanceProjection::class, $projection->id, 'exception', null, null, null); continue; }
                $debit = (int) $account->entries()->where('side', 'debit')->sum('amount_minor');
                $credit = (int) $account->entries()->where('side', 'credit')->sum('amount_minor');
                $actual = $account->normal_side === 'credit' ? $credit - $debit : $debit - $credit;
                $status = $actual === $projection->posted_minor ? 'matched' : 'exception';
                if ($status === 'exception') $exceptions++;
                $this->item($run, 'balance_projection', BalanceProjection::class, $projection->id, $status, $actual, $projection->posted_minor, $actual - $projection->posted_minor, ['currency_code' => $projection->currency_code]);
                $held = (int) WalletHold::where('wallet_currency_account_id', $projection->wallet_currency_account_id)->where('status', 'active')->sum('amount_minor');
                $holdStatus = $held === $projection->held_minor ? 'matched' : 'exception';
                if ($holdStatus === 'exception') $exceptions++;
                $this->item($run, 'holds', BalanceProjection::class, $projection->id, $holdStatus, $held, $projection->held_minor, $held - $projection->held_minor, ['currency_code' => $projection->currency_code]);
            }
            $run->update(['status' => $exceptions ? 'completed_with_exceptions' : 'completed', 'summary' => ['exceptions' => $exceptions], 'completed_at' => now()]);
            return $run->load('items');
        });
    }

    private function item(ReconciliationRun $run, string $type, string $entityType, string $entityId, string $status, ?int $expected, ?int $actual, ?int $difference, array $metadata = []): void
    {
        ReconciliationItem::create(['reconciliation_run_id' => $run->id, 'item_type' => $type, 'entity_type' => $entityType, 'entity_id' => $entityId, 'status' => $status, 'expected_minor' => $expected, 'actual_minor' => $actual, 'difference_minor' => $difference, 'metadata' => $metadata]);
    }
}
