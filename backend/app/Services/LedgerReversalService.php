<?php

namespace App\Services;

use App\Domain\Ledger\FinancialCommand;
use App\Domain\Ledger\LedgerLine;
use App\Models\LedgerReversal;
use App\Models\LedgerTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class LedgerReversalService
{
    public function __construct(private LedgerPostingService $posting) {}

    public function reverse(LedgerTransaction $original, string $reason, string $actorId, string $idempotencyKey): LedgerTransaction
    {
        if ($original->status !== 'posted') throw ValidationException::withMessages(['transaction' => 'Only posted transactions can be reversed.']);
        return DB::transaction(function () use ($original, $reason, $actorId, $idempotencyKey): LedgerTransaction {
            if (LedgerReversal::where('original_transaction_id', $original->id)->exists()) throw ValidationException::withMessages(['transaction' => 'This transaction has already been reversed.']);
            $original->load('entries');
            $lines = $original->entries->map(fn ($entry) => new LedgerLine($entry->ledger_account_id, $entry->currency_code, $entry->side === 'debit' ? 'credit' : 'debit', $entry->amount_minor, ['reversal_of_entry' => $entry->id]))->all();
            $reversal = $this->posting->post(new FinancialCommand('reversal', 'REV-'.$original->reference.'-'.Str::upper(Str::random(8)), $lines, $reason, ['original_transaction_id' => $original->id], false), $actorId, $idempotencyKey, 'ledger.reversal');
            LedgerReversal::create(['original_transaction_id' => $original->id, 'reversal_transaction_id' => $reversal->id, 'actor_id' => $actorId, 'reason' => $reason]);
            return $reversal;
        });
    }
}
