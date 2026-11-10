<?php

namespace App\Services;

use App\Domain\Ledger\FinancialCommand;
use App\Domain\Ledger\LedgerLine;
use App\Models\Merchant;
use App\Models\PaymentIntent;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RefundService
{
    public function __construct(private MerchantAccessService $access, private IdempotencyService $idempotency, private LedgerPostingService $posting) {}

    public function request(User $user, Merchant $merchant, PaymentIntent $intent, int $amountMinor, string $reason, string $idempotencyKey): RefundRequest
    {
        $this->access->assert($user, $merchant, ['owner', 'manager', 'finance']);
        if ((string) $intent->merchant_id !== (string) $merchant->id || $intent->operation !== 'merchant_payment' || $intent->status !== 'succeeded') throw ValidationException::withMessages(['payment' => 'Payment is not refundable by this merchant.']);
        if ($amountMinor <= 0 || $amountMinor > (int) $intent->amount_minor) throw ValidationException::withMessages(['amount_minor' => 'Refund amount is invalid.']);
        $existing = RefundRequest::where('merchant_id', $merchant->id)->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            if ((string) $existing->payment_intent_id !== (string) $intent->id || (int) $existing->amount_minor !== $amountMinor || (string) $existing->reason !== $reason) throw ValidationException::withMessages(['idempotency_key' => 'The refund idempotency key was reused with a different request.']);
            return $existing;
        }
        return RefundRequest::create(['merchant_id' => $merchant->id, 'payment_intent_id' => $intent->id, 'requested_by' => $user->id, 'idempotency_key' => $idempotencyKey, 'amount_minor' => $amountMinor, 'currency_code' => $intent->currency_code, 'reason' => $reason, 'status' => 'requested', 'metadata' => ['sandbox_only' => true]]);
    }

    public function approve(User $approver, RefundRequest $request): RefundRequest
    {
        $merchant = $request->merchant()->firstOrFail();
        $this->access->assert($approver, $merchant, ['owner', 'manager']);
        return DB::transaction(function () use ($approver, $request, $merchant): RefundRequest {
            $locked = RefundRequest::with('paymentIntent')->whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'requested') throw ValidationException::withMessages(['refund' => 'Only requested refunds can be approved.']);
            $intent = PaymentIntent::whereKey($locked->payment_intent_id)->lockForUpdate()->firstOrFail();
            $refunded = (int) RefundRequest::where('payment_intent_id', $intent->id)->where('status', 'succeeded')->sum('amount_minor');
            if ($refunded + (int) $locked->amount_minor > (int) $intent->amount_minor) throw ValidationException::withMessages(['refund' => 'Refund exceeds the refundable amount.']);
            $originalId = $intent->metadata['ledger_transaction_id'] ?? null;
            $original = $originalId ? \App\Models\LedgerTransaction::with('entries')->find($originalId) : null;
            if (!$original || $original->status !== 'posted') throw ValidationException::withMessages(['refund' => 'Original ledger payment is unavailable.']);
            $debit = $original->entries->firstWhere('side', 'debit');
            $credit = $original->entries->firstWhere('side', 'credit');
            if (!$debit || !$credit) throw ValidationException::withMessages(['refund' => 'Original payment journal is invalid.']);
            $ledger = $this->posting->post(new FinancialCommand('merchant_refund', 'MCH-REF-'.$locked->id, [
                new LedgerLine($debit->ledger_account_id, $locked->currency_code, 'credit', $locked->amount_minor, ['refund_request_id' => $locked->id]),
                new LedgerLine($credit->ledger_account_id, $locked->currency_code, 'debit', $locked->amount_minor, ['refund_request_id' => $locked->id]),
            ], 'Sandbox merchant refund', ['refund_request_id' => $locked->id, 'original_transaction_id' => $original->id, 'sandbox_only' => true]), $approver->id, 'merchant-refund-'.$locked->id, 'merchant.refund');
            $locked->update(['status' => 'succeeded', 'approved_by' => $approver->id, 'approved_at' => now(), 'completed_at' => now(), 'ledger_transaction_id' => $ledger->id]);
            app(FinancialAuditLogger::class)->record('merchant.refund.succeeded', RefundRequest::class, $locked->id, ['merchant_id' => $merchant->id, 'payment_intent_id' => $intent->id, 'amount_minor' => $locked->amount_minor, 'sandbox_only' => true], actorId: $approver->id);
            return $locked->refresh();
        });
    }
}
