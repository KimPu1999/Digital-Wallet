<?php

namespace App\Domain\Reporting;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class ReportQueryService
{
    public const CURRENCIES = ['USD', 'EUR', 'GBP', 'SGD', 'THB', 'MMK'];

    public function period(array $parameters = []): array
    {
        $from = isset($parameters['from']) ? Carbon::parse($parameters['from'])->startOfDay() : now()->subDays(30)->startOfDay();
        $to = isset($parameters['to']) ? Carbon::parse($parameters['to'])->endOfDay() : now()->endOfDay();
        if ($from->greaterThan($to)) throw new \InvalidArgumentException('The report start date must be before the end date.');
        if ($from->diffInDays($to) > 366) throw new \InvalidArgumentException('Report ranges are limited to 366 days.');
        return [$from, $to];
    }

    public function overview(array $parameters = []): array
    {
        [$from, $to] = $this->period($parameters);
        $payments = $this->paymentSummary($from, $to);
        $transfers = $this->transferSummary($from, $to);
        $ledger = $this->ledgerSummary($from, $to);
        return [
            'period' => ['from' => $from->toIso8601String(), 'to' => $to->toIso8601String()],
            'sandbox_only' => true,
            'transaction_volume_by_currency' => $payments['volume_by_currency'],
            'successful_payments' => $payments['successful'],
            'failed_payments' => $payments['failed'],
            'pending_transfers' => $transfers['pending'],
            'wallet_liabilities_by_currency' => $ledger['liabilities'],
            'held_funds_by_currency' => $this->heldByCurrency(),
            'fee_revenue_by_currency' => $ledger['revenue'],
            'fx_fee_revenue_by_currency' => $this->fxFeeRevenue($from, $to),
            'refunds_by_currency' => $this->refundTotals($from, $to),
            'settlement_exceptions' => DB::table('payment_reconciliation_items')->where('status', 'open')->count(),
            'reconciliation' => DB::table('reconciliation_runs')->latest('started_at')->first(['status', 'summary', 'completed_at']),
        ];
    }

    public function trialBalance(array $parameters = []): array
    {
        [$from, $to] = $this->period($parameters);
        $groups = $this->ledgerGroups($from, $to);
        $opening = $this->ledgerSideTotals(null, $from, true);
        $movement = $this->ledgerSideTotals($from, $to);
        $rows = [];
        foreach ($groups as $key => $group) {
            $open = array_merge(['debit' => 0, 'credit' => 0], $opening[$key] ?? []);
            $move = array_merge(['debit' => 0, 'credit' => 0], $movement[$key] ?? []);
            $normal = $group->normal_side === 'credit' ? -1 : 1;
            $rows[] = [
                'account_code' => $group->code,
                'account_name' => $group->name,
                'category' => $group->category,
                'currency_code' => $group->currency_code,
                'opening_balance_minor' => $normal * ((int) $open['debit'] - (int) $open['credit']),
                'debit_minor' => (int) $move['debit'],
                'credit_minor' => (int) $move['credit'],
                'closing_balance_minor' => $normal * ((int) $open['debit'] - (int) $open['credit'] + (int) $move['debit'] - (int) $move['credit']),
            ];
        }
        return ['period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()], 'rows' => $rows, 'balanced_by_currency' => $this->balanced($movement), 'sandbox_only' => true];
    }

    public function generalLedger(array $parameters = []): LengthAwarePaginator
    {
        [$from, $to] = $this->period($parameters);
        $query = $this->postedLedgerQuery($from, $to)->select(['e.id', 'e.currency_code', 'e.side', 'e.amount_minor', 'e.sequence', 'e.created_at', 'a.code as account_code', 'a.name as account_name', 't.reference as journal_reference', 't.description', 't.id as ledger_transaction_id'])->orderBy('e.created_at')->orderBy('e.sequence');
        if (!empty($parameters['currency'])) $query->where('e.currency_code', strtoupper((string) $parameters['currency']));
        if (!empty($parameters['account_code'])) $query->where('a.code', $parameters['account_code']);
        $page = $query->paginate(10);
        $page->getCollection()->transform(fn ($row) => (array) $row + ['running_balance_minor' => $this->runningBalance($row, $from)]);
        return $page;
    }

    public function profitLoss(array $parameters = []): array
    {
        [$from, $to] = $this->period($parameters);
        $rows = $this->categoryTotals($from, $to, ['revenue', 'expense']);
        $byCurrency = [];
        foreach ($rows as $row) {
            $value = $row['category'] === 'revenue' ? $row['credit_minor'] - $row['debit_minor'] : $row['debit_minor'] - $row['credit_minor'];
            $byCurrency[$row['currency_code']]['revenue_minor'] = ($byCurrency[$row['currency_code']]['revenue_minor'] ?? 0) + ($row['category'] === 'revenue' ? $value : 0);
            $byCurrency[$row['currency_code']]['expense_minor'] = ($byCurrency[$row['currency_code']]['expense_minor'] ?? 0) + ($row['category'] === 'expense' ? $value : 0);
            $byCurrency[$row['currency_code']]['net_income_minor'] = ($byCurrency[$row['currency_code']]['net_income_minor'] ?? 0) + ($row['category'] === 'revenue' ? $value : -$value);
        }
        return ['period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()], 'by_currency' => $byCurrency, 'classification_source' => 'posted ledger account categories', 'sandbox_only' => true];
    }

    public function balanceSheet(array $parameters = []): array
    {
        [$from, $to] = $this->period($parameters);
        $rows = $this->categoryTotals(null, $to, ['asset', 'liability', 'equity']);
        $byCurrency = [];
        foreach ($rows as $row) {
            $value = in_array($row['category'], ['liability', 'equity'], true) ? $row['credit_minor'] - $row['debit_minor'] : $row['debit_minor'] - $row['credit_minor'];
            $byCurrency[$row['currency_code']][$row['category'].'_minor'] = ($byCurrency[$row['currency_code']][$row['category'].'_minor'] ?? 0) + $value;
        }
        foreach ($byCurrency as $currency => &$row) {
            $row['assets_minor'] = $row['asset_minor'] ?? 0;
            $row['liabilities_minor'] = $row['liability_minor'] ?? 0;
            $row['equity_minor'] = $row['equity_minor'] ?? 0;
            $row['difference_minor'] = $row['assets_minor'] - ($row['liabilities_minor'] + $row['equity_minor']);
            unset($row['asset_minor'], $row['liability_minor']);
        }
        return ['as_of' => $to->toDateString(), 'by_currency' => $byCurrency, 'balanced' => collect($byCurrency)->every(fn ($row) => $row['difference_minor'] === 0), 'sandbox_only' => true];
    }

    public function wallets(array $parameters = []): LengthAwarePaginator
    {
        return DB::table('balance_projections as p')->join('wallet_currency_accounts as a', 'a.id', '=', 'p.wallet_currency_account_id')->join('wallets as w', 'w.id', '=', 'a.wallet_id')->leftJoin('wallet_owners as o', 'o.id', '=', 'w.owner_id')->when(!empty($parameters['currency']), fn ($q) => $q->where('p.currency_code', strtoupper((string) $parameters['currency'])))->when(!empty($parameters['status']), fn ($q) => $q->where('a.status', $parameters['status']))->select(['p.wallet_currency_account_id as wallet_currency_account_id', 'p.currency_code', 'p.posted_minor', 'p.held_minor', DB::raw('(p.posted_minor - p.held_minor) as available_minor'), 'a.status as account_status', 'w.id as wallet_id', 'w.wallet_type', 'o.owner_type'])->orderBy('w.id')->paginate(10);
    }

    public function walletHolds(array $parameters = []): LengthAwarePaginator
    {
        return DB::table('wallet_holds')->when(!empty($parameters['currency']), fn ($q) => $q->where('currency_code', strtoupper((string) $parameters['currency'])))->when(!empty($parameters['status']), fn ($q) => $q->where('status', $parameters['status']))->select(['id', 'wallet_currency_account_id', 'currency_code', 'amount_minor', 'status', 'reference', 'expires_at', 'created_at'])->latest()->paginate(10);
    }

    public function transfers(array $parameters = []): LengthAwarePaginator
    {
        [$from, $to] = $this->period($parameters);
        return DB::table('transfers')->whereBetween('created_at', [$from, $to])->when(!empty($parameters['status']), fn ($q) => $q->where('status', $parameters['status']))->when(!empty($parameters['currency']), fn ($q) => $q->where('source_currency_code', strtoupper((string) $parameters['currency'])))->select(['id', 'transfer_reference', 'source_currency_code', 'destination_currency_code', 'source_amount_minor', 'recipient_amount_minor', 'provider_fee_minor', 'platform_fee_minor', 'status', 'sandbox_provider_reference', 'ledger_transaction_id', 'created_at', 'completed_at'])->latest()->paginate(10);
    }

    public function fx(array $parameters = []): LengthAwarePaginator
    {
        [$from, $to] = $this->period($parameters);
        return DB::table('fx_conversions')->whereBetween('created_at', [$from, $to])->when(!empty($parameters['status']), fn ($q) => $q->where('status', $parameters['status']))->when(!empty($parameters['currency']), fn ($q) => $q->where('source_currency_code', strtoupper((string) $parameters['currency'])))->select(['id', 'source_currency_code', 'target_currency_code', 'source_amount_minor', 'target_amount_minor', 'fee_minor', 'exchange_rate', 'status', 'ledger_transaction_id', 'created_at', 'completed_at'])->latest()->paginate(10);
    }

    public function payments(array $parameters = [], ?string $merchantId = null): LengthAwarePaginator
    {
        [$from, $to] = $this->period($parameters);
        return DB::table('payment_intents')->whereBetween('created_at', [$from, $to])->when($merchantId, fn ($q) => $q->where('merchant_id', $merchantId))->when(!empty($parameters['status']), fn ($q) => $q->where('status', $parameters['status']))->when(!empty($parameters['currency']), fn ($q) => $q->where('currency_code', strtoupper((string) $parameters['currency'])))->select(['id', 'merchant_id', 'intent_reference', 'operation', 'payment_method', 'currency_code', 'amount_minor', 'fee_minor', 'status', 'settlement_reference', 'created_at', 'completed_at'])->latest()->paginate(10);
    }

    public function refunds(array $parameters = [], ?string $merchantId = null): LengthAwarePaginator
    {
        [$from, $to] = $this->period($parameters);
        return DB::table('refund_requests')->whereBetween('created_at', [$from, $to])->when($merchantId, fn ($q) => $q->where('merchant_id', $merchantId))->when(!empty($parameters['status']), fn ($q) => $q->where('status', $parameters['status']))->select(['id', 'merchant_id', 'payment_intent_id', 'currency_code', 'amount_minor', 'reason', 'status', 'provider_reference', 'ledger_transaction_id', 'created_at', 'completed_at'])->latest()->paginate(10);
    }

    public function settlements(array $parameters = [], ?string $merchantId = null): LengthAwarePaginator
    {
        [$from, $to] = $this->period($parameters);
        return DB::table($merchantId ? 'merchant_settlements' : 'settlement_batches')->whereBetween('created_at', [$from, $to])->when($merchantId, fn ($q) => $q->where('merchant_id', $merchantId))->when(!empty($parameters['currency']), fn ($q) => $q->where('currency_code', strtoupper((string) $parameters['currency'])))->select($merchantId ? ['id', 'merchant_id', 'settlement_reference', 'currency_code', 'gross_amount_minor', 'fee_amount_minor', 'refund_amount_minor', 'net_amount_minor', 'status', 'period_start', 'period_end', 'settled_at'] : ['id', 'provider_id', 'settlement_reference', 'currency_code', 'total_amount_minor', 'total_fee_minor', 'status', 'period_start', 'period_end', 'settled_at'])->latest()->paginate(10);
    }

    public function reconciliation(array $parameters = []): LengthAwarePaginator
    {
        return DB::table('reconciliation_runs')->latest('started_at')->paginate(10);
    }

    public function merchantOverview(array $parameters, string $merchantId): array
    {
        [$from, $to] = $this->period($parameters);
        $rows = DB::table('payment_intents')->where('merchant_id', $merchantId)->whereBetween('created_at', [$from, $to])->get(['status', 'currency_code', 'amount_minor', 'fee_minor']);
        $refunds = DB::table('refund_requests')->where('merchant_id', $merchantId)->whereBetween('created_at', [$from, $to])->get(['currency_code', 'amount_minor', 'status']);
        return ['period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()], 'total_payments' => $rows->count(), 'successful_payments' => $rows->where('status', 'succeeded')->count(), 'failed_payments' => $rows->whereIn('status', ['failed', 'cancelled'])->count(), 'gross_by_currency' => $rows->where('status', 'succeeded')->groupBy('currency_code')->map(fn ($items) => (int) $items->sum('amount_minor')), 'fees_by_currency' => $rows->where('status', 'succeeded')->groupBy('currency_code')->map(fn ($items) => (int) $items->sum('fee_minor')), 'refunds_by_currency' => $refunds->whereIn('status', ['approved', 'completed', 'succeeded'])->groupBy('currency_code')->map(fn ($items) => (int) $items->sum('amount_minor')), 'sandbox_only' => true];
    }

    private function postedLedgerQuery(?Carbon $from, ?Carbon $to)
    {
        return DB::table('ledger_entries as e')->join('ledger_transactions as t', 't.id', '=', 'e.ledger_transaction_id')->join('ledger_accounts as a', 'a.id', '=', 'e.ledger_account_id')->where('t.status', 'posted')->when($from, fn ($q) => $q->where('e.created_at', '>=', $from))->when($to, fn ($q) => $q->where('e.created_at', '<=', $to));
    }

    private function ledgerSideTotals(?Carbon $from, ?Carbon $to, bool $before = false): array
    {
        $rows = $this->postedLedgerQuery(null, $before ? null : $to)->when($before && $to, fn ($q) => $q->where('e.created_at', '<', $to))->when(!$before && $from, fn ($q) => $q->where('e.created_at', '>=', $from))->select(['e.ledger_account_id', 'e.currency_code', 'e.side'])->selectRaw('SUM(e.amount_minor) as total')->groupBy('e.ledger_account_id', 'e.currency_code', 'e.side')->get();
        $result = [];
        foreach ($rows as $row) $result[$row->ledger_account_id.'|'.$row->currency_code][$row->side] = (int) $row->total;
        return $result;
    }

    private function ledgerGroups(?Carbon $from, ?Carbon $to)
    {
        return $this->postedLedgerQuery($from, $to)->select(['a.code', 'a.name', 'a.category', 'a.normal_side', 'a.id as ledger_account_id', 'e.currency_code'])->groupBy('a.code', 'a.name', 'a.category', 'a.normal_side', 'a.id', 'e.currency_code')->get()->keyBy(fn ($row) => $row->ledger_account_id.'|'.$row->currency_code);
    }

    private function categoryTotals(?Carbon $from, ?Carbon $to, array $categories): array
    {
        $rows = $this->postedLedgerQuery($from, $to)->whereIn('a.category', $categories)->select(['a.category', 'e.currency_code', 'e.side'])->selectRaw('SUM(e.amount_minor) as total')->groupBy('a.category', 'e.currency_code', 'e.side')->get();
        $out = [];
        foreach ($rows as $row) $out[] = ['category' => $row->category, 'currency_code' => $row->currency_code, 'debit_minor' => $row->side === 'debit' ? (int) $row->total : 0, 'credit_minor' => $row->side === 'credit' ? (int) $row->total : 0];
        $grouped = [];
        foreach ($out as $row) { $key = $row['category'].'|'.$row['currency_code']; foreach (['debit_minor','credit_minor'] as $field) $grouped[$key][$field] = ($grouped[$key][$field] ?? 0) + $row[$field]; $grouped[$key]['category'] = $row['category']; $grouped[$key]['currency_code'] = $row['currency_code']; }
        return array_values($grouped);
    }

    private function balanced(array $totals): array
    {
        $byCurrency = [];
        foreach ($totals as $key => $row) { [, $currency] = explode('|', $key, 2); $byCurrency[$currency]['debit_minor'] = ($byCurrency[$currency]['debit_minor'] ?? 0) + ($row['debit'] ?? 0); $byCurrency[$currency]['credit_minor'] = ($byCurrency[$currency]['credit_minor'] ?? 0) + ($row['credit'] ?? 0); }
        foreach ($byCurrency as &$row) $row['balanced'] = $row['debit_minor'] === $row['credit_minor'];
        return $byCurrency;
    }

    private function paymentSummary(Carbon $from, Carbon $to): array
    {
        $rows = DB::table('payment_intents')->whereBetween('created_at', [$from, $to])->get(['status', 'currency_code', 'amount_minor']);
        return ['successful' => $rows->where('status', 'succeeded')->count(), 'failed' => $rows->whereIn('status', ['failed', 'cancelled'])->count(), 'volume_by_currency' => $rows->where('status', 'succeeded')->groupBy('currency_code')->map(fn ($items) => (int) $items->sum('amount_minor'))];
    }

    private function transferSummary(Carbon $from, Carbon $to): array { return ['pending' => DB::table('transfers')->whereBetween('created_at', [$from, $to])->whereIn('status', ['draft', 'processing', 'pending', 'submitted'])->count()]; }
    private function heldByCurrency(): array { return DB::table('wallet_holds')->where('status', 'active')->select('currency_code')->selectRaw('SUM(amount_minor) as amount_minor')->groupBy('currency_code')->pluck('amount_minor', 'currency_code')->map(fn ($value) => (int) $value)->all(); }
    private function refundTotals(Carbon $from, Carbon $to): array { return DB::table('refund_requests')->whereBetween('created_at', [$from, $to])->whereIn('status', ['approved', 'completed', 'succeeded'])->select('currency_code')->selectRaw('SUM(amount_minor) as amount_minor')->groupBy('currency_code')->pluck('amount_minor', 'currency_code')->map(fn ($value) => (int) $value)->all(); }
    private function fxFeeRevenue(Carbon $from, Carbon $to): array { return DB::table('fx_conversions')->whereBetween('created_at', [$from, $to])->whereIn('status', ['completed', 'succeeded'])->select('source_currency_code')->selectRaw('SUM(fee_minor) as fee_minor')->groupBy('source_currency_code')->pluck('fee_minor', 'source_currency_code')->map(fn ($value) => (int) $value)->all(); }
    private function ledgerSummary(Carbon $from, Carbon $to): array { $rows = $this->categoryTotals(null, $to, ['liability', 'revenue']); $out = ['liabilities' => [], 'revenue' => []]; foreach ($rows as $row) { $value = $row['category'] === 'liability' ? $row['credit_minor'] - $row['debit_minor'] : $row['credit_minor'] - $row['debit_minor']; $key = $row['category'] === 'liability' ? 'liabilities' : 'revenue'; $out[$key][$row['currency_code']] = ($out[$key][$row['currency_code']] ?? 0) + $value; } return $out; }
    private function runningBalance(object $row, Carbon $from): int { $prior = $this->postedLedgerQuery(null, Carbon::parse($row->created_at))->where('a.code', $row->account_code)->where('e.currency_code', $row->currency_code)->selectRaw("SUM(CASE WHEN e.side = 'debit' THEN e.amount_minor ELSE -e.amount_minor END) as balance")->value('balance'); return (int) $prior; }
}
