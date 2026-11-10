<?php

namespace App\Domain\RegulatoryReadiness;

use Illuminate\Support\Facades\DB;

final class RegulatoryReadinessService
{
    private const GATES = [
        ['gate_key' => 'legal_authorization', 'name' => 'Legal authorization'],
        ['gate_key' => 'licensing_status', 'name' => 'Licensing status'],
        ['gate_key' => 'partner_approval', 'name' => 'Banking/PSP partner approval'],
        ['gate_key' => 'kyc_kyb_readiness', 'name' => 'KYC/KYB readiness'],
        ['gate_key' => 'aml_readiness', 'name' => 'AML operational readiness'],
        ['gate_key' => 'safeguarding_validation', 'name' => 'Safeguarding validation'],
        ['gate_key' => 'consumer_protection', 'name' => 'Consumer protection review'],
        ['gate_key' => 'security_assessment', 'name' => 'Independent security assessment'],
        ['gate_key' => 'privacy_assessment', 'name' => 'Privacy assessment'],
        ['gate_key' => 'operational_resilience', 'name' => 'Operational resilience'],
        ['gate_key' => 'incident_readiness', 'name' => 'Incident readiness'],
        ['gate_key' => 'executive_approval', 'name' => 'Executive approval'],
    ];

    public function overview(): array
    {
        $gateCounts = DB::table('launch_readiness_gates')->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');
        $openExceptions = DB::table('safeguarding_exceptions')->whereIn('status', ['open', 'investigating', 'escalated'])->count();
        $openFindings = DB::table('assurance_findings')->whereIn('status', ['open', 'in_progress'])->count();

        return [
            'generated_at' => now()->toIso8601String(),
            'sandbox_only' => true,
            'live_financial_execution_enabled' => false,
            'jurisdictions' => DB::table('regulatory_jurisdictions')->count(),
            'activity_assessments' => DB::table('regulated_activity_assessments')->count(),
            'licensing_records' => DB::table('license_records')->count(),
            'partners' => DB::table('financial_partners')->count(),
            'partner_production_approvals' => DB::table('partner_capability_approvals')->where('environment', 'production')->where('status', 'approved')->count(),
            'open_safeguarding_exceptions' => $openExceptions,
            'open_assurance_findings' => $openFindings,
            'pending_evidence' => DB::table('regulatory_evidence')->whereIn('approval_status', ['pending_review', 'rejected'])->count(),
            'launch_gates' => $gateCounts,
            'launch_status' => $this->launchStatus(),
            'currency_reconciliation' => DB::table('safeguarding_reconciliations')->select('currency_code', 'status', DB::raw('count(*) as total'))->groupBy('currency_code', 'status')->orderBy('currency_code')->get(),
        ];
    }

    public function jurisdictions() { return DB::table('regulatory_jurisdictions')->latest('updated_at')->paginate(10); }
    public function activities() { return DB::table('regulated_activity_assessments')->join('regulatory_jurisdictions', 'regulatory_jurisdictions.id', '=', 'regulated_activity_assessments.jurisdiction_id')->select('regulated_activity_assessments.*', 'regulatory_jurisdictions.code as jurisdiction_code', 'regulatory_jurisdictions.name as jurisdiction_name')->latest('regulated_activity_assessments.updated_at')->paginate(10); }
    public function licenses() { return DB::table('license_records')->join('regulatory_jurisdictions', 'regulatory_jurisdictions.id', '=', 'license_records.jurisdiction_id')->select('license_records.*', 'regulatory_jurisdictions.code as jurisdiction_code')->latest('license_records.updated_at')->paginate(10); }
    public function obligations() { return DB::table('regulatory_obligations')->join('regulatory_jurisdictions', 'regulatory_jurisdictions.id', '=', 'regulatory_obligations.jurisdiction_id')->select('regulatory_obligations.*', 'regulatory_jurisdictions.code as jurisdiction_code')->latest('regulatory_obligations.updated_at')->paginate(10); }
    public function partners() { return DB::table('financial_partners')->latest('updated_at')->paginate(10); }
    public function partner(string $id): array
    {
        return [
            'partner' => DB::table('financial_partners')->where('id', $id)->firstOrFail(),
            'due_diligence' => DB::table('partner_due_diligence')->where('partner_id', $id)->select(['id', 'partner_id', 'status', 'review_due_at', 'created_at', 'updated_at'])->latest('created_at')->get(),
            'agreements' => DB::table('partner_agreements')->where('partner_id', $id)->select(['id', 'partner_id', 'jurisdiction_id', 'status', 'effective_date', 'expires_at', 'approved_scope', 'created_at', 'updated_at'])->latest('created_at')->get(),
        ];
    }
    public function partnerCapabilities(string $id) { return DB::table('partner_capability_approvals')->where('partner_id', $id)->latest('updated_at')->paginate(10); }
    public function safeguardingOverview(): array
    {
        $ledgerExpected = DB::table('ledger_entries as e')
            ->join('ledger_transactions as t', 't.id', '=', 'e.ledger_transaction_id')
            ->join('ledger_accounts as a', 'a.id', '=', 'e.ledger_account_id')
            ->where('t.status', 'posted')
            ->where('a.category', 'liability')
            ->whereNotNull('a.wallet_currency_account_id')
            ->select('e.currency_code')
            ->selectRaw("SUM(CASE WHEN e.side = 'credit' THEN e.amount_minor ELSE -e.amount_minor END) as expected_amount_minor")
            ->groupBy('e.currency_code')->get()->keyBy('currency_code');
        $recorded = DB::table('safeguarding_reconciliations')->select('currency_code', DB::raw('sum(expected_amount_minor) as recorded_expected_amount_minor'), DB::raw('sum(coalesce(verified_external_amount_minor, 0)) as verified_external_amount_minor'), DB::raw('sum(coalesce(variance_minor, 0)) as variance_minor'))->groupBy('currency_code')->get()->keyBy('currency_code');
        $currencyCodes = $ledgerExpected->keys()->merge($recorded->keys())->unique()->sort()->values();
        return [
            'generated_at' => now()->toIso8601String(),
            'sandbox_only' => true,
            'source_of_truth' => 'posted ledger data plus verified external evidence; no dashboard adjustments',
            'by_currency' => $currencyCodes->map(function (string $currency) use ($ledgerExpected, $recorded): array {
                $ledger = $ledgerExpected->get($currency);
                $snapshot = $recorded->get($currency);
                return [
                    'currency_code' => $currency,
                    'expected_amount_minor' => $ledger ? (string) $ledger->expected_amount_minor : (string) ($snapshot->recorded_expected_amount_minor ?? 0),
                    'expected_amount_source' => $ledger ? 'posted_wallet_liability_ledger' : 'reconciliation_snapshot_pending_ledger_evidence',
                    'recorded_expected_amount_minor' => (string) ($snapshot->recorded_expected_amount_minor ?? 0),
                    'verified_external_amount_minor' => (string) ($snapshot->verified_external_amount_minor ?? 0),
                    'variance_minor' => (string) ($snapshot->variance_minor ?? 0),
                ];
            })->values(),
            'open_exceptions' => DB::table('safeguarding_exceptions')->whereIn('status', ['open', 'investigating', 'escalated'])->count(),
        ];
    }
    public function reconciliations() { return DB::table('safeguarding_reconciliations')->latest('reconciled_at')->latest('created_at')->paginate(10); }
    public function exceptions() { return DB::table('safeguarding_exceptions')->latest('created_at')->paginate(10); }
    public function assessments() { return DB::table('compliance_assessments')->latest('updated_at')->paginate(10); }
    public function evidence() { return DB::table('regulatory_evidence')->select(['id', 'category', 'title', 'jurisdiction_id', 'source_reference', 'reviewed_at', 'expires_at', 'version', 'approval_status', 'access_classification', 'created_at', 'updated_at'])->latest('updated_at')->paginate(10); }
    public function readiness(): array
    {
        $stored = DB::table('launch_readiness_gates')->get()->keyBy('gate_key');
        return ['status' => $this->launchStatus(), 'sandbox_only' => true, 'live_financial_execution_enabled' => false, 'gates' => collect(self::GATES)->map(function (array $gate) use ($stored): array { $row = $stored->get($gate['gate_key']); return array_merge($gate, $row ? (array) $row : ['status' => 'not_started', 'evidence_reference' => null, 'verified_at' => null]); })->values()->all(), 'pilot_count' => DB::table('pilot_authorizations')->count(), 'pending_launch_approvals' => DB::table('admin_approval_requests')->where('action', 'regulatory.launch.assessment')->where('status', 'pending')->count()];
    }
    public function gates() { return DB::table('launch_readiness_gates')->latest('updated_at')->paginate(10); }
    public function pilots() { return DB::table('pilot_authorizations')->latest('created_at')->paginate(10); }

    public function launchStatus(): string
    {
        $stored = DB::table('launch_readiness_gates')->pluck('status', 'gate_key');
        foreach (self::GATES as $gate) if (($stored[$gate['gate_key']] ?? 'not_started') !== 'verified') return 'blocked';
        return 'conditional';
    }

    public function defaultGateDefinitions(): array { return self::GATES; }
}
