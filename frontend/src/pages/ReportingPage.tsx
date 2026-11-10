import { BarChart3, Download, FileText, RefreshCcw, ShieldCheck, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { apiFetch } from '../lib/api';
import { AppShell } from '../components/layout/AppShell';

const labels: Record<string, string> = { overview: 'Financial overview', 'trial-balance': 'Trial balance', 'general-ledger': 'General ledger', 'profit-loss': 'Profit & loss', 'balance-sheet': 'Balance sheet', wallets: 'Wallet reports', 'wallet-holds': 'Wallet holds', transfers: 'Transfer reports', fx: 'FX reports', payments: 'Payment reports', refunds: 'Refund reports', settlements: 'Settlement reports', reconciliation: 'Reconciliation', revenue: 'Revenue analytics', 'transaction-volume': 'Transaction volume', 'currency-exposure': 'Currency exposure' };

export function ReportingPage() {
  const path = window.location.pathname;
  const merchant = path.startsWith('/merchant/reports');
  const section = path.split('/').filter(Boolean).pop() || 'overview';
  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');
  const merchantId = import.meta.env.VITE_DEMO_MERCHANT_ID as string | undefined;
  const base = merchant ? `/merchant/reports/${section}?merchant_id=${encodeURIComponent(merchantId || '')}` : `/platform/reports/${section}`;
  const query = useQuery({ queryKey: ['report', path, from, to, merchantId], queryFn: () => apiFetch<{ data: any; meta?: any }>(base + (from || to ? `&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}` : '')), enabled: !merchant || Boolean(merchantId) });
  const data = query.data?.data;
  const rows = Array.isArray(data) ? data : Array.isArray(data?.rows) ? data.rows : Array.isArray(data?.by_currency) ? data.by_currency : [];
  return <AppShell><main className="page reporting-page"><div className="page-heading"><div><span className="eyebrow">{merchant ? 'MERCHANT REPORTING' : 'PLATFORM REPORTING'}</span><h1>{labels[section] || 'Report center'}</h1><p className="subtle">Read-only reporting from posted ledger records and verified sandbox data.</p></div><span className="demo-badge"><Sparkles size={13}/> Sandbox only</span></div><div className="demo-notice"><ShieldCheck size={17}/><span><strong>No balance edits.</strong> Currency totals remain separated unless an explicit valuation is recorded.</span></div><div className="report-toolbar"><label>From<input type="date" value={from} onChange={(event) => setFrom(event.target.value)}/></label><label>To<input type="date" value={to} onChange={(event) => setTo(event.target.value)}/></label><button className="secondary-btn" onClick={() => { setFrom(''); setTo(''); }}><RefreshCcw size={15}/> Reset</button><button className="secondary-btn" disabled={!query.data}><Download size={15}/> Export center</button></div>{query.isLoading && <div className="admin-state"><BarChart3 size={20}/>Loading authorized report data…</div>}{query.isError && <div className="admin-state error">Unable to load this report. Verify authorization and reporting period.</div>}{!query.isLoading && !query.isError && <><Summary data={data}/><section className="side-card admin-directory"><div className="side-card-head"><div><h2>{labels[section] || 'Report results'}</h2><p>{merchant ? 'Merchant-scoped records only.' : 'Posted ledger source of truth; pending activity is excluded.'} · 10 rows per page</p></div><FileText size={20}/></div>{rows.length ? rows.slice(0, 10).map((row: any, index: number) => <div className="admin-table-row" key={row.id || index}>{Object.entries(row).slice(0, 5).map(([key, value]) => <span key={key}><strong>{typeof value === 'object' ? JSON.stringify(value) : String(value ?? '—')}</strong><small>{key.replaceAll('_', ' ')}</small></span>)}</div>) : <div className="admin-empty">No authorized records for this period.</div>}</section></>}</main></AppShell>;
}

function Summary({ data }: { data: any }) { const entries = data && !Array.isArray(data) ? Object.entries(data).filter(([key, value]) => ['number', 'string'].includes(typeof value) || key.endsWith('_by_currency')) .slice(0, 8) : []; return <div className="admin-kpi-grid">{entries.map(([key, value]) => <div className="ledger-metric" key={key}><span>{key.replaceAll('_', ' ')}</span><strong>{typeof value === 'object' ? Object.keys(value as object).length + ' currencies' : String(value)}</strong><small>Sandbox aggregate</small></div>)}</div>; }
