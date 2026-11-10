import { Activity, AlertTriangle, FileSearch, LockKeyhole, Search, ShieldAlert } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { apiFetch } from '../lib/api';
import { AppShell } from '../components/layout/AppShell';

type Section = 'security' | 'aml' | 'screening' | 'fraud' | 'cases';
const nav: Array<{ key: Section; label: string; path: string }> = [
  { key: 'security', label: 'Security overview', path: '/admin/security' },
  { key: 'aml', label: 'AML monitoring', path: '/admin/aml' },
  { key: 'screening', label: 'Sanctions screening', path: '/admin/screening' },
  { key: 'fraud', label: 'Fraud monitoring', path: '/admin/fraud' },
  { key: 'cases', label: 'Compliance cases', path: '/admin/compliance-cases' },
];
const endpoints: Record<Section, string> = { security: '/platform/security/overview', aml: '/platform/aml/alerts', screening: '/platform/screening/matches', fraud: '/platform/fraud/alerts', cases: '/platform/compliance/cases' };

export function PlatformSecurityPage({ section = 'security' }: { section?: Section }) {
  const query = useQuery({ queryKey: ['platform-security', section], queryFn: () => apiFetch<{ data: any; meta?: any }>(endpoints[section]) });
  return <AppShell><main className="page platform-admin-page"><div className="page-heading"><div><span className="eyebrow">PLATFORM SECURITY & COMPLIANCE</span><h1>{nav.find((item) => item.key === section)?.label}</h1><p className="subtle">Explainable sandbox monitoring with analyst review required.</p></div><span className="demo-badge"><ShieldAlert size={13}/> Sandbox only</span></div><div className="demo-notice"><LockKeyhole size={17}/><span><strong>No automatic clearance.</strong> Screening failures and potential matches remain reviewable and never authorize financial activity.</span></div><nav className="platform-nav" aria-label="Security administration">{nav.map((item) => <Link className={section === item.key ? 'active' : ''} key={item.key} to={item.path}>{item.label}</Link>)}</nav>{query.isLoading && <div className="admin-state"><Activity size={20}/>Loading authorized monitoring data…</div>}{query.isError && <div className="admin-state error"><AlertTriangle size={20}/>Unable to load this protected view. Verify an authorized platform role and MFA.</div>}{!query.isLoading && !query.isError && <Content section={section} data={query.data?.data}/>}</main></AppShell>;
}

function Content({ section, data }: { section: Section; data: any }) {
  if (section === 'security') return <Overview data={data}/>;
  const rows = Array.isArray(data) ? data : section === 'aml' ? data?.alerts ?? [] : section === 'screening' ? data?.matches ?? [] : section === 'fraud' ? data?.alerts ?? [] : data?.cases ?? [];
  return <section className="side-card admin-directory"><div className="side-card-head"><div><h2>{section === 'aml' ? 'AML alerts' : section === 'screening' ? 'Potential screening matches' : section === 'fraud' ? 'Fraud alerts' : 'Compliance case queue'}</h2><p>10 rows per page · server-side authorization and filtering.</p></div><Search size={18}/></div>{rows.length ? rows.map((row: any, index: number) => <div className="admin-table-row" key={row.id ?? index}><span><strong>{row.reason ?? row.status ?? row.match_reference ?? '—'}</strong><small>{row.category ?? row.list_source ?? row.case_type ?? 'monitoring record'}</small></span><span><strong>{row.severity ?? row.priority ?? row.status ?? '—'}</strong><small>status</small></span><span><strong>{row.subject_id ?? row.provider ?? '—'}</strong><small>subject / source</small></span><span><strong>{row.created_at ?? '—'}</strong><small>recorded</small></span></div>) : <div className="admin-empty">No records returned from the sandbox database.</div>}</section>;
}

function Overview({ data }: { data: any }) {
  const cards = [['Open alerts', data?.open_alerts], ['High-risk alerts', data?.high_risk_alerts], ['Screening reviews', data?.pending_screening_reviews], ['Compliance cases', data?.compliance_cases], ['Auth anomalies', data?.authentication_anomalies], ['Rule failures', data?.rule_evaluation_failures]];
  return <><div className="admin-kpi-grid">{cards.map(([label, value]) => <div className="ledger-metric" key={String(label)}><span>{label}</span><strong>{value ?? '—'}</strong><small>Authorized sandbox aggregate</small></div>)}</div><div className="platform-columns"><section className="side-card"><div className="side-card-head"><div><span className="eyebrow">CONTROL SIGNALS</span><h2>Monitoring posture</h2></div><ShieldAlert size={20}/></div><div className="admin-row"><span>Required screening</span><strong className="warning">Manual review</strong><small>Potential matches are never auto-cleared</small></div><div className="admin-row"><span>Rule decisions</span><strong className="green">Explainable</strong><small>Versioned configuration with evidence references</small></div><div className="admin-row"><span>Financial actions</span><strong className="green">Disabled</strong><small>Monitoring cannot post or edit ledger balances</small></div></section><section className="side-card"><div className="side-card-head"><div><span className="eyebrow">PROVIDER HEALTH</span><h2>Screening adapters</h2></div><FileSearch size={20}/></div>{(data?.screening_provider_health ?? []).map((provider: any) => <div className="admin-row" key={provider.provider}><span>{provider.provider}</span><strong className={Number(provider.failures) ? 'warning' : 'green'}>{Number(provider.failures) ? 'review failures' : 'available'}</strong><small>{provider.total} requests · sandbox provider</small></div>)}{!data?.screening_provider_health?.length && <div className="admin-empty">No screening provider activity returned.</div>}</section></div></>;
}
