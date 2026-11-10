import { AlertTriangle, Building2, FileCheck2, Globe2, LockKeyhole, Scale, ShieldCheck } from 'lucide-react';
import { useQuery } from '@tanstack/react-query';
import { apiFetch } from '../lib/api';
import { AppShell } from '../components/layout/AppShell';
import { useLanguage } from '../hooks/useLanguage';

type Overview = {
  generated_at: string;
  sandbox_only: boolean;
  live_financial_execution_enabled: boolean;
  jurisdictions: number;
  activity_assessments: number;
  licensing_records: number;
  partners: number;
  partner_production_approvals: number;
  open_safeguarding_exceptions: number;
  open_assurance_findings: number;
  pending_evidence: number;
  launch_status: string;
  currency_reconciliation: Array<{ currency_code: string; status: string; total: number }>;
};

type Readiness = {
  status: string;
  live_financial_execution_enabled: boolean;
  gates: Array<{ gate_key: string; name: string; status: string; evidence_reference?: string | null }>;
  pilot_count: number;
  pending_launch_approvals: number;
};

type Partner = { id: string; name: string; partner_type: string; onboarding_status: string; api_readiness: string; sandbox_only: boolean };

function Status({ value }: { value: string }) {
  const normalized = value.toLowerCase();
  const positive = ['verified', 'approved', 'active', 'completed'].includes(normalized);
  const negative = ['blocked', 'failed', 'rejected', 'expired'].includes(normalized);
  return <span className={`status-chip ${positive ? 'ready' : negative ? 'blocked' : 'conditional'}`}>{value.replaceAll('_', ' ')}</span>;
}

export function RegulatoryReadinessPage() {
  const { language, toggleLanguage, copy } = useLanguage();
  const overview = useQuery({ queryKey: ['regulatory-overview'], queryFn: () => apiFetch<{ data: Overview }>('/platform/regulatory/overview') });
  const readiness = useQuery({ queryKey: ['launch-readiness'], queryFn: () => apiFetch<{ data: Readiness }>('/platform/launch/readiness') });
  const partners = useQuery({ queryKey: ['regulatory-partners'], queryFn: () => apiFetch<{ data: Partner[] }>('/platform/regulatory/partners') });
  const safeguarding = useQuery({ queryKey: ['safeguarding-overview'], queryFn: () => apiFetch<{ data: { by_currency: Array<{ currency_code: string; expected_amount_minor: string; verified_external_amount_minor: string; variance_minor: string }>; open_exceptions: number } }>('/platform/safeguarding/overview') });
  const data = overview.data?.data;
  const gates = readiness.data?.data;

  return <AppShell><main className="page platform-admin-page regulatory-page">
    <div className="page-heading"><div><span className="eyebrow">{copy.regulatory.eyebrow}</span><h1>{copy.regulatory.title}</h1><p className="subtle">{copy.regulatory.description}</p></div><div className="topbar-actions"><button className="lang-btn" onClick={toggleLanguage} aria-label="Switch language"><Globe2 size={15}/> {language.toUpperCase()}</button><span className="demo-badge"><LockKeyhole size={13}/> {copy.regulatory.sandbox}</span></div></div>
    <div className="demo-notice"><ShieldCheck size={17}/><span><strong>{copy.regulatory.noticeTitle}</strong> {copy.regulatory.noticeBody}</span></div>
    {(overview.isLoading || readiness.isLoading) && <div className="admin-state"><Scale size={18}/> {copy.regulatory.loading}</div>}
    {(overview.isError || readiness.isError) && <div className="admin-state error"><AlertTriangle size={18}/> {copy.regulatory.error}</div>}
    {data && <>
      <div className="admin-kpi-grid"><Metric label={copy.regulatory.jurisdictions} value={data.jurisdictions} icon={<Scale size={15}/>} /><Metric label={copy.regulatory.assessments} value={data.activity_assessments} icon={<FileCheck2 size={15}/>} /><Metric label={copy.regulatory.licenses} value={data.licensing_records} icon={<FileCheck2 size={15}/>} /><Metric label={copy.regulatory.partners} value={data.partners} icon={<Building2 size={15}/>} /><Metric label={copy.regulatory.exceptions} value={data.open_safeguarding_exceptions} icon={<AlertTriangle size={15}/>} /><Metric label={copy.regulatory.findings} value={data.open_assurance_findings} icon={<AlertTriangle size={15}/>} /><Metric label={copy.regulatory.evidence} value={data.pending_evidence} icon={<FileCheck2 size={15}/>} /></div>
      <div className="platform-columns">
        <section className="side-card"><div className="side-card-head"><div><span className="eyebrow">{copy.regulatory.goNoGo}</span><h2>{copy.regulatory.launchReadiness}</h2></div><Status value={data.launch_status}/></div><p className="subtle">Missing evidence remains blocked. Independent approval is required for any pilot assessment.</p>{gates?.gates.map((gate) => <div className="admin-row" key={gate.gate_key}><span><strong>{gate.name}</strong><small>{gate.evidence_reference ? 'Evidence reference recorded' : 'Evidence required'}</small></span><Status value={gate.status}/></div>)}{gates && <small className="admin-footnote">{gates.pending_launch_approvals} pending independent approval(s) · {gates.pilot_count} pilot plan(s)</small>}</section>
        <section className="side-card"><div className="side-card-head"><div><span className="eyebrow">{copy.regulatory.partnerGovernance}</span><h2>{copy.regulatory.partnerDirectory}</h2></div><Building2 size={20}/></div>{partners.data?.data?.map((partner) => <div className="admin-row" key={partner.id}><span><strong>{partner.name}</strong><small>{partner.partner_type} · {partner.sandbox_only ? copy.regulatory.sandbox : 'review required'}</small></span><Status value={partner.onboarding_status}/></div>)}{partners.data?.data?.length === 0 && <div className="admin-empty">{copy.regulatory.noPartners}</div>}</section>
      </div>
      <section className="side-card qa-section"><div className="side-card-head"><div><span className="eyebrow">{copy.regulatory.safeguarding}</span><h2>{copy.regulatory.reconciliation}</h2><p>{copy.regulatory.currencyNote}</p></div><ShieldCheck size={20}/></div>{safeguarding.data?.data.by_currency.map((row) => <div className="admin-row" key={row.currency_code}><span><strong>{row.currency_code}</strong><small>Expected {row.expected_amount_minor} minor units · verified external {row.verified_external_amount_minor}</small></span><span>Variance {row.variance_minor}</span></div>)}{!safeguarding.data?.data.by_currency.length && <div className="admin-empty">{copy.regulatory.noEvidence}</div>}<small className="admin-footnote">Open exceptions: {safeguarding.data?.data.open_exceptions ?? '—'}. No manual balance adjustment is available here.</small></section>
      <small className="admin-footnote">Observed at {data.generated_at}. Legal, licensing, partner and safeguarding statuses are not approvals unless evidence has been independently verified.</small>
    </>}
  </main></AppShell>;
}

function Metric({ label, value, icon }: { label: string; value: number; icon: React.ReactNode }) { return <div className="ledger-metric"><span>{icon} {label}</span><strong>{value}</strong><small>Authorized sandbox metadata</small></div>; }
