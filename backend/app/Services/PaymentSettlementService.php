<?php

namespace App\Services;

use App\Domain\Ledger\FinancialCommand;
use App\Domain\Ledger\LedgerLine;
use App\Domain\Payments\ProviderWebhookEvent;
use App\Models\PaymentIntent;
use App\Models\ProviderTransaction;
use App\Models\SettlementBatch;
use App\Models\SettlementItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PaymentSettlementService
{
    public function __construct(private LedgerPostingService $posting, private ChartOfAccountsService $chart, private WalletHoldService $holds, private PaymentReconciliationService $reconciliation, private ProviderStatusMapper $statuses) {}

    public function apply(PaymentIntent $intent, ProviderTransaction $providerTransaction, ProviderWebhookEvent $event): PaymentIntent
    {
        return DB::transaction(function () use ($intent, $providerTransaction, $event): PaymentIntent {
            $intent = PaymentIntent::whereKey($intent->id)->lockForUpdate()->firstOrFail();
            if (in_array($intent->status, ['succeeded', 'failed', 'returned', 'cancelled', 'expired'], true)) return $intent;
            if ($providerTransaction->currency_code !== $intent->currency_code || (int) $providerTransaction->amount_minor !== (int) $intent->amount_minor) {
                $this->reconciliation->exception($intent, $providerTransaction, 'amount_or_currency_mismatch', 'Provider amount or currency does not match the payment intent.', $intent->amount_minor, $providerTransaction->amount_minor);
                return $this->transitionUnknown($intent);
            }

            $mapped = $this->statuses->intentStatus($event->status);
            if ($mapped === 'processing') return $intent;
            if (in_array($mapped, ['failed', 'returned'], true)) {
                $this->releaseWithdrawalHold($intent);
                $intent->update(['status' => $mapped, 'failure_reason' => 'Provider reported '.$mapped, 'completed_at' => now()]);
                return $intent->refresh();
            }
            if ($mapped === 'unknown') return $this->transitionUnknown($intent);

            $ledgerTransactionId = null;
            if ($intent->operation === 'deposit') {
                $account = $intent->walletCurrencyAccount()->firstOrFail();
                $walletLedger = $this->chart->provisionWalletAccount($account);
                $clearing = $this->chart->provisionSystemAccount('system.provider.clearing.'.strtolower($intent->provider?->code ?? 'sandbox').'.'.strtolower($intent->currency_code), 'Sandbox provider clearing '.$intent->currency_code, 'asset', 'debit', $intent->currency_code, true);
                $ledger = $this->posting->post(new FinancialCommand('provider_deposit_settlement', 'PAY-DEP-'.$intent->id, [
                    new LedgerLine($clearing->id, $intent->currency_code, 'debit', (int) $intent->amount_minor),
                    new LedgerLine($walletLedger->id, $intent->currency_code, 'credit', (int) $intent->amount_minor),
                ], 'Sandbox provider deposit settlement', ['payment_intent_id' => $intent->id, 'sandbox_only' => true], false), $intent->user_id, 'payment-deposit-settle-'.$intent->id, 'payment.deposit.settlement');
                $ledgerTransactionId = $ledger->id;
            } elseif ($intent->operation === 'withdrawal') {
                $hold = $this->holdFor($intent);
                if (!$hold) throw ValidationException::withMessages(['payment' => 'Withdrawal hold is missing.']);
                $clearing = $this->chart->provisionSystemAccount('system.provider.payout.'.strtolower($intent->provider?->code ?? 'sandbox').'.'.strtolower($intent->currency_code), 'Sandbox provider payout '.$intent->currency_code, 'asset', 'debit', $intent->currency_code, true);
                $captured = $this->holds->capture($intent->user()->firstOrFail(), $hold, $clearing->id, 'payment-withdrawal-capture-'.$intent->id);
                $ledgerTransactionId = $captured->metadata['capture_transaction_id'] ?? null;
            }

            $batch = SettlementBatch::firstOrCreate(['provider_id' => $intent->provider_id, 'settlement_reference' => 'SET-'.$intent->id], ['currency_code' => $intent->currency_code, 'status' => 'settled', 'total_amount_minor' => $intent->amount_minor, 'total_fee_minor' => $intent->fee_minor, 'settled_at' => now(), 'metadata' => ['sandbox_only' => true]]);
            SettlementItem::firstOrCreate(['settlement_batch_id' => $batch->id, 'payment_intent_id' => $intent->id], ['provider_transaction_id' => $providerTransaction->id, 'ledger_transaction_id' => $ledgerTransactionId, 'currency_code' => $intent->currency_code, 'amount_minor' => $intent->amount_minor, 'fee_minor' => $intent->fee_minor, 'status' => 'matched', 'metadata' => ['sandbox_only' => true]]);
            $intent->update(['status' => 'succeeded', 'settlement_reference' => $batch->settlement_reference, 'completed_at' => now()]);
            return $intent->refresh();
        });
    }

    private function transitionUnknown(PaymentIntent $intent): PaymentIntent
    {
        $intent->update(['status' => 'unknown', 'failure_reason' => 'Provider outcome requires reconciliation.']);
        $this->reconciliation->exception($intent, null, 'unknown_provider_outcome', 'Provider outcome is unknown; no retry or ledger posting was performed.', null, null);
        return $intent->refresh();
    }

    private function holdFor(PaymentIntent $intent): ?\App\Models\WalletHold
    {
        $holdId = $intent->metadata['hold_id'] ?? null;
        return $holdId ? \App\Models\WalletHold::whereKey($holdId)->lockForUpdate()->first() : null;
    }

    private function releaseWithdrawalHold(PaymentIntent $intent): void
    {
        if ($intent->operation !== 'withdrawal') return;
        $hold = $this->holdFor($intent);
        if ($hold && $hold->status === 'active') $this->holds->release($intent->user()->firstOrFail(), $hold, 'payment-withdrawal-release-'.$intent->id);
    }
}
