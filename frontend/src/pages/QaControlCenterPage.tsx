import { AlertTriangle, CheckCircle2, ClipboardCheck, Clock3, DatabaseBackup, Gauge, LockKeyhole, ShieldAlert } from 'lucide-react';
import { useQuery } from '@tanstack/react-query';
import { apiFetch } from '../lib/api';
import { AppShell } from '../components/layout/AppShell';

type Gate = { gate_key: string; status: 'ready' | 'conditional' | 'blocked'; reason?: string; evidence_reference?: string | null };
type QaOverview = {
  sandbox_only: boolean;
  test_runs: { total: number; latest: Array<{ suite: string; category: string; status: string; finished_at?: string }> };
  findings: { open: number; critical: number };
  performance_runs: Array<{ target: string; status: string; p95_ms?: string | number }>;
  recovery_drills: Array<{ environment: string; status: string; ledger_reconciled?: boolean }>;
  release_gates: Gate[];
};

const labels: Record<string, string> = {
  critical_functional: 'Critical functional tests',
  financial_integrity: 'Financial integrity',
  authorization_regression: 'Authorization regression',
  performance_evaluated: 'Performance evidence',
  backup_restore_verified: 'Backup restore',
  monitoring_configured: 'Monitoring configuration',
};

function statusIcon(status: string) {
  if (status === 'ready' || status === 'passed') return <CheckCircle2 size={16} />;
  if (status === 'conditional') return <AlertTriangle size={16} />;
  return <ShieldAlert size={16} />;
}

export function QaControlCenterPage() {
  const query = useQuery({ queryKey: ['platform-qa-overview'], queryFn: () => apiFetch<{ data: QaOverview }>('/platform/qa/overview') });
  const data = query.data?.data;
  const overall = data?.release_gates?.some((gate) => gate.status === 'blocked') ? 'blocked' : data?.release_gates?.some((gate) => gate.status === 'conditional') ? 'conditional' : 'ready';

  return <AppShell><main className="page platform-admin-page">
    <div className="page-heading"><div><span className="eyebrow">QUALITY & RELEASE ASSURANCE</span><h1>QA control center</h1><p className="subtle">Evidence-backed readiness for the sandbox platform. Missing evidence remains blocked.</p></div><span className={`status-chip ${overall}`}>
      {statusIcon(overall)} {overall.toUpperCase()}
    </span></div>
    <div className="demo-notice"><LockKeyhole size={17}/><span><strong>Sandbox only.</strong> This view reports stored test and recovery evidence; it never enables financial execution.</span></div>
    {query.isLoading && <div className="admin-state">Loading QA evidence…</div>}
    {query.isError && <div className="admin-state error">Unable to load QA evidence. Verify platform authorization and API availability.</div>}
    {data && <>
      <div className="admin-kpi-grid">
        <div className="ledger-metric"><span><ClipboardCheck size={15}/> Recorded test runs</span><strong>{data.test_runs.total}</strong><small>Stored evidence only</small></div>
        <div className="ledger-metric"><span><ShieldAlert size={15}/> Open findings</span><strong>{data.findings.open}</strong><small>{data.findings.critical} critical</small></div>
        <div className="ledger-metric"><span><Gauge size={15}/> Performance runs</span><strong>{data.performance_runs.length}</strong><small>Measured artifacts only</small></div>
        <div className="ledger-metric"><span><DatabaseBackup size={15}/> Recovery drills</span><strong>{data.recovery_drills.length}</strong><small>Non-production evidence</small></div>
      </div>
      <section className="side-card admin-directory qa-section"><div className="side-card-head"><div><h2>Release gates</h2><p>Each gate requires current, recorded evidence.</p></div><Clock3 size={20}/></div>
        <div className="qa-gates">{data.release_gates.map((gate) => <div className="qa-gate" key={gate.gate_key}><span><strong>{labels[gate.gate_key] ?? gate.gate_key}</strong><small>{gate.reason ?? (gate.evidence_reference || 'Evidence reference recorded')}</small></span><span className={`status-chip ${gate.status}`}>{statusIcon(gate.status)} {gate.status}</span></div>)}</div>
      </section>
      <section className="side-card admin-directory qa-section"><div className="side-card-head"><div><h2>Latest evidence</h2><p>Only results persisted by QA workflows are shown.</p></div><ClipboardCheck size={20}/></div>
        {data.test_runs.latest.length ? data.test_runs.latest.map((run) => <div className="admin-row" key={`${run.category}-${run.suite}`}><span><strong>{run.suite}</strong><small>{run.category}</small></span><span className={`status-chip ${run.status}`}>{statusIcon(run.status)} {run.status}</span></div>) : <div className="admin-empty">No test-run artifacts have been recorded.</div>}
      </section>
    </>}
  </main></AppShell>;
}
