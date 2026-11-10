import { useQuery } from '@tanstack/react-query';
import { AlertTriangle, BarChart3, ClipboardCheck, Database, ShieldCheck, Sparkles } from 'lucide-react';
import type { ElementType } from 'react';
import { apiFetch } from '../lib/api';

type FxOverview = { quotes: number; conversions: number; completed_conversions: number; failed_conversions: number; enabled_pairs: number; providers: number };

export function FxAdminPage() {
  const { data, isLoading, isError } = useQuery({ queryKey: ['admin-fx-overview'], queryFn: () => apiFetch<{ data: FxOverview }>('/admin/fx/overview') });
  const metrics = data?.data;
  const cards: Array<{ label: string; value: string | number; icon: ElementType }> = [{ label: 'Quotes', value: metrics?.quotes ?? '—', icon: BarChart3 }, { label: 'Completed', value: metrics?.completed_conversions ?? '—', icon: ClipboardCheck }, { label: 'Failed', value: metrics?.failed_conversions ?? '—', icon: AlertTriangle }, { label: 'Pairs', value: metrics?.enabled_pairs ?? '—', icon: Database }];
  return <main className="page"><div className="page-heading"><div><span className="eyebrow">PLATFORM CONTROL CENTER</span><h1>FX monitoring</h1><p className="subtle">Operational visibility for sandbox quotes, conversions, and reconciliation.</p></div><span className="demo-badge"><Sparkles size={13}/> Sandbox only</span></div><div className="demo-notice"><ShieldCheck size={17}/><span><strong>Read-only monitoring.</strong> No rate editing, balance editing, provider settlement, or arbitrary journal posting is available here.</span></div>{isError && <div className="wallet-error"><AlertTriangle size={17}/><div>Unable to load FX monitoring data.<small>Check admin authorization and API availability.</small></div></div>}<div className="ledger-overview-grid">{cards.map(({ label, value }) => <div className="ledger-metric" key={label}><span>{label}</span><strong>{isLoading ? '…' : value}</strong><small>Sandbox records</small></div>)}</div><section className="ledger-panel"><div><span className="eyebrow">RECONCILIATION</span><h2>Conversion integrity checks</h2><p>Run the server-side FX reconciliation command to compare completed conversion legs against posted journals.</p></div><div className="ledger-panel-actions"><span>Per-currency balancing</span><span>Immutable ledger link</span><span>No auto-correction</span></div></section></main>;
}
