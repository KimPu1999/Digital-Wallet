<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantSettlement;
use App\Models\PaymentIntent;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Support\Str;

final class MerchantSettlementService
{
    public function __construct(private MerchantAccessService $access) {}

    public function report(User $user, Merchant $merchant, ?string $from = null, ?string $to = null): array
    {
        $this->access->assert($user, $merchant);
        $fromDate = $from ? now()->parse($from)->startOfDay() : now()->subDays(30)->startOfDay();
        $toDate = $to ? now()->parse($to)->endOfDay() : now()->endOfDay();
        $payments = PaymentIntent::where('merchant_id', $merchant->id)->where('operation', 'merchant_payment')->where('status', 'succeeded')->whereBetween('completed_at', [$fromDate, $toDate])->get();
        $refunds = RefundRequest::where('merchant_id', $merchant->id)->where('status', 'succeeded')->whereBetween('completed_at', [$fromDate, $toDate])->get()->groupBy('currency_code');
        $rows = [];
        foreach ($payments->groupBy('currency_code') as $currency => $items) {
            $gross = (int) $items->sum('amount_minor');
            $refund = (int) ($refunds->get($currency)?->sum('amount_minor') ?? 0);
            $reference = 'MSET-'.$merchant->id.'-'.str_replace('-', '', $fromDate->toDateString()).'-'.str_replace('-', '', $toDate->toDateString()).'-'.$currency;
            $rows[] = MerchantSettlement::updateOrCreate(['settlement_reference' => $reference], ['merchant_id' => $merchant->id, 'currency_code' => $currency, 'gross_amount_minor' => $gross, 'fee_amount_minor' => 0, 'refund_amount_minor' => $refund, 'net_amount_minor' => $gross - $refund, 'status' => 'projected', 'period_start' => $fromDate, 'period_end' => $toDate, 'metadata' => ['sandbox_only' => true, 'evidence' => 'internal wallet ledger']]);
        }
        return $rows;
    }
}
