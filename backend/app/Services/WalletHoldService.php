<?php

namespace App\Services;

use App\Domain\Ledger\FinancialCommand;
use App\Domain\Ledger\LedgerLine;
use App\Models\User;
use App\Models\WalletCurrencyAccount;
use App\Models\WalletHold;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class WalletHoldService
{
    public function __construct(private LedgerPostingService $posting, private FinancialAuditLogger $audit) {}

    public function create(User $user, WalletCurrencyAccount $account, int $amountMinor, ?string $reference, ?string $idempotencyKey, ?\DateTimeInterface $expiresAt = null): WalletHold
    {
        $this->assertOwnership($user, $account);
        if ($amountMinor <= 0) throw ValidationException::withMessages(['amount_minor' => 'Hold amount must be positive.']);
        $work = function () use ($user, $account, $amountMinor, $reference, $expiresAt): WalletHold {
            return DB::transaction(function () use ($user, $account, $amountMinor, $reference, $expiresAt): WalletHold {
                $projection = $account->projection()->lockForUpdate()->firstOrFail();
                if ($projection->posted_minor - $projection->held_minor < $amountMinor) throw ValidationException::withMessages(['balance' => 'Insufficient available funds for hold.']);
                $hold = WalletHold::create(['wallet_currency_account_id' => $account->id, 'currency_code' => $account->currency()->value('code'), 'amount_minor' => $amountMinor, 'status' => 'active', 'reference' => $reference, 'expires_at' => $expiresAt, 'created_by' => $user->id]);
                $projection->update(['held_minor' => $projection->held_minor + $amountMinor, 'version' => $projection->version + 1]);
                $this->audit->record('wallet.hold.created', WalletHold::class, $hold->id, ['amount_minor' => $amountMinor, 'currency_code' => $hold->currency_code], actorId: $user->id);
                return $hold;
            });
        };
        if (!$idempotencyKey) return $work();
        $result = app(IdempotencyService::class)->execute('wallet.hold.create', $idempotencyKey, ['wallet_currency_account_id' => $account->id, 'amount_minor' => $amountMinor, 'reference' => $reference, 'expires_at' => $expiresAt?->format(DATE_ATOM)], fn () => ['hold_id' => $work()->id]);
        return WalletHold::findOrFail($result->value['hold_id']);
    }

    public function release(User $user, WalletHold $hold, ?string $idempotencyKey = null): WalletHold
    {
        $this->assertOwnership($user, $hold->walletCurrencyAccount()->firstOrFail());
        return $this->transition($hold, $user->id, 'released', $idempotencyKey);
    }

    public function capture(User $user, WalletHold $hold, string $counterpartyAccountId, string $idempotencyKey): WalletHold
    {
        $account = $hold->walletCurrencyAccount()->firstOrFail();
        $this->assertOwnership($user, $account);
        return DB::transaction(function () use ($user, $hold, $account, $counterpartyAccountId, $idempotencyKey): WalletHold {
            $lockedHold = WalletHold::whereKey($hold->id)->lockForUpdate()->firstOrFail();
            if ($lockedHold->status !== 'active') throw ValidationException::withMessages(['hold' => 'Only an active hold can be captured.']);
            $projection = $account->projection()->lockForUpdate()->firstOrFail();
            $transaction = $this->posting->post(new FinancialCommand('hold_capture', 'CAP-'.$lockedHold->id, [new LedgerLine($account->ledgerAccount()->value('id'), $lockedHold->currency_code, 'debit', $lockedHold->amount_minor), new LedgerLine($counterpartyAccountId, $lockedHold->currency_code, 'credit', $lockedHold->amount_minor)], 'Capture wallet hold', ['hold_id' => $lockedHold->id], false), $user->id, $idempotencyKey, 'wallet.hold.capture');
            $projection->refresh();
            $projection->update(['held_minor' => $projection->held_minor - $lockedHold->amount_minor, 'version' => $projection->version + 1]);
            $lockedHold->update(['status' => 'captured', 'captured_at' => now(), 'metadata' => ['capture_transaction_id' => $transaction->id]]);
            $this->audit->record('wallet.hold.captured', WalletHold::class, $lockedHold->id, ['transaction_id' => $transaction->id], actorId: $user->id);
            return $lockedHold->refresh();
        });
    }

    public function expire(int $limit = 100): int
    {
        $count = 0;
        WalletHold::where('status', 'active')->whereNotNull('expires_at')->where('expires_at', '<=', now())->limit($limit)->get()->each(function (WalletHold $hold) use (&$count): void {
            DB::transaction(function () use ($hold, &$count): void {
                $locked = WalletHold::whereKey($hold->id)->lockForUpdate()->first();
                if (!$locked || $locked->status !== 'active') return;
                $projection = $locked->walletCurrencyAccount()->firstOrFail()->projection()->lockForUpdate()->firstOrFail();
                $projection->update(['held_minor' => max(0, $projection->held_minor - $locked->amount_minor), 'version' => $projection->version + 1]);
                $locked->update(['status' => 'expired', 'released_at' => now()]);
                $count++;
            });
        });
        return $count;
    }

    private function transition(WalletHold $hold, string $actorId, string $status, ?string $idempotencyKey): WalletHold
    {
        $work = function () use ($hold, $actorId, $status): WalletHold {
            return DB::transaction(function () use ($hold, $actorId, $status): WalletHold {
                $locked = WalletHold::whereKey($hold->id)->lockForUpdate()->firstOrFail();
                if ($locked->status === $status) return $locked;
                if ($locked->status !== 'active') throw ValidationException::withMessages(['hold' => 'Only an active hold can be released.']);
                $projection = $locked->walletCurrencyAccount()->firstOrFail()->projection()->lockForUpdate()->firstOrFail();
                $projection->update(['held_minor' => max(0, $projection->held_minor - $locked->amount_minor), 'version' => $projection->version + 1]);
                $locked->update(['status' => $status, 'released_at' => now()]);
                $this->audit->record('wallet.hold.'.$status, WalletHold::class, $locked->id, [], actorId: $actorId);
                return $locked->refresh();
            });
        };
        if (!$idempotencyKey) return $work();
        $result = app(IdempotencyService::class)->execute('wallet.hold.'.$status, $idempotencyKey, ['hold_id' => $hold->id], fn () => ['hold_id' => $work()->id]);
        return WalletHold::findOrFail($result->value['hold_id']);
    }

    private function assertOwnership(User $user, WalletCurrencyAccount $account): void
    {
        $wallet = $account->wallet()->with('owner')->firstOrFail();
        abort_unless(app(\App\Policies\WalletPolicy::class)->view($user, $wallet), 404, 'Wallet account not found.');
    }
}
