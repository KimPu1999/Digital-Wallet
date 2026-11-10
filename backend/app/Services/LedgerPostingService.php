<?php

namespace App\Services;

use App\Domain\Ledger\FinancialCommand;
use App\Domain\Ledger\LedgerLine;
use App\Models\FinancialAuditEvent;
use App\Models\IdempotencyKey;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\OutboxEvent;
use App\Models\WalletCurrencyAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class LedgerPostingService
{
    public function __construct(private IdempotencyService $idempotency, private BalanceProjectionService $projections, private FinancialAuditLogger $audit) {}

    public function post(FinancialCommand $command, ?string $actorId = null, ?string $idempotencyKey = null, string $scope = 'ledger.post'): LedgerTransaction
    {
        $work = fn () => DB::transaction(fn () => $this->postOnce($command, $actorId));
        if ($idempotencyKey === null) return $work();
        $result = $this->idempotency->execute($scope, $idempotencyKey, $this->fingerprintPayload($command), function () use ($work, $scope, $idempotencyKey): array {
            $transaction = $work();
            $idempotency = IdempotencyKey::where('scope', $scope)->where('key', $idempotencyKey)->first();
            if ($idempotency) $transaction->update(['idempotency_key_id' => $idempotency->id]);
            return ['transaction_id' => $transaction->id, 'reference' => $transaction->reference, 'status' => $transaction->status];
        });
        return LedgerTransaction::with('entries.account')->findOrFail($result->value['transaction_id']);
    }

    private function postOnce(FinancialCommand $command, ?string $actorId): LedgerTransaction
    {
        if (count($command->lines) < 2) throw ValidationException::withMessages(['entries' => 'A journal requires at least two entries.']);
        $accountIds = collect($command->lines)->map(fn (LedgerLine $line) => $line->accountId)->unique()->sort()->values();
        $accounts = LedgerAccount::whereIn('id', $accountIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        if ($accounts->count() !== $accountIds->count()) throw ValidationException::withMessages(['entries' => 'One or more ledger accounts do not exist.']);
        $totals = [];
        foreach ($command->lines as $line) {
            $account = $accounts->get($line->accountId);
            if ($account->status !== 'active') throw ValidationException::withMessages(['entries' => 'Posting to a disabled ledger account is not allowed.']);
            if ($account->currency_code !== $line->currencyCode) throw ValidationException::withMessages(['currency' => 'Ledger account currency does not match the entry currency.']);
            if ($account->restricted && $actorId === null) throw ValidationException::withMessages(['authorization' => 'This ledger account requires an authorized actor.']);
            $totals[$line->currencyCode] ??= ['debit' => 0, 'credit' => 0];
            $totals[$line->currencyCode][$line->side] = $this->safeAdd($totals[$line->currencyCode][$line->side], $line->amountMinor);
        }
        foreach ($totals as $currency => $total) if ($total['debit'] !== $total['credit']) throw ValidationException::withMessages(['entries' => "Journal is unbalanced for {$currency}."]);

        if ($command->checkAvailableFunds) {
            foreach ($command->lines as $line) {
                $account = $accounts->get($line->accountId);
                if ($line->side !== 'debit' || $account->normal_side !== 'credit' || !$account->wallet_currency_account_id) continue;
                $projection = $account->walletCurrencyAccount()->firstOrFail()->projection()->lockForUpdate()->firstOrFail();
                if ($projection->posted_minor - $projection->held_minor < $line->amountMinor) throw ValidationException::withMessages(['balance' => 'Insufficient available funds.']);
            }
        }

        $transaction = LedgerTransaction::create(['reference' => $command->reference, 'transaction_type' => $command->transactionType, 'status' => 'pending', 'actor_id' => $actorId, 'description' => $command->description, 'metadata' => $command->metadata]);
        foreach ($command->lines as $sequence => $line) LedgerEntry::create(['ledger_transaction_id' => $transaction->id, 'ledger_account_id' => $line->accountId, 'currency_code' => $line->currencyCode, 'side' => $line->side, 'amount_minor' => $line->amountMinor, 'sequence' => $sequence + 1, 'metadata' => $line->metadata]);
        $transaction->update(['status' => 'posted', 'posted_at' => now()]);
        foreach ($command->lines as $line) {
            $account = $accounts->get($line->accountId);
            if ($account->wallet_currency_account_id) $this->projections->applyLedgerDelta($account->walletCurrencyAccount()->firstOrFail(), $account, $line->side, $line->amountMinor, $transaction->posted_at);
        }
        $this->audit->record('ledger.transaction.posted', LedgerTransaction::class, $transaction->id, ['reference' => $transaction->reference, 'transaction_type' => $transaction->transaction_type], actorId: $actorId);
        OutboxEvent::create(['event_type' => 'ledger.transaction.posted', 'aggregate_type' => LedgerTransaction::class, 'aggregate_id' => $transaction->id, 'payload' => ['transaction_id' => $transaction->id, 'reference' => $transaction->reference], 'available_at' => now()]);
        return $transaction->load('entries.account');
    }

    private function safeAdd(int $left, int $right): int
    {
        if ($right > PHP_INT_MAX - $left) throw ValidationException::withMessages(['amount' => 'Ledger amount overflow.']);
        return $left + $right;
    }

    private function fingerprintPayload(FinancialCommand $command): array
    {
        return ['transaction_type' => $command->transactionType, 'reference' => $command->reference, 'description' => $command->description, 'metadata' => $command->metadata, 'lines' => array_map(fn (LedgerLine $line) => ['account_id' => $line->accountId, 'currency_code' => $line->currencyCode, 'side' => $line->side, 'amount_minor' => $line->amountMinor, 'metadata' => $line->metadata], $command->lines)];
    }
}
