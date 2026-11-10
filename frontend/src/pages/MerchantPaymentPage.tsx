import { BarChart3, ClipboardList, Code2, CreditCard, Link2, QrCode, RefreshCcw, Settings2, ShieldCheck, Sparkles, Webhook, WalletCards } from 'lucide-react';
import { Link, useLocation } from 'react-router-dom';
import { AppShell } from '../components/layout/AppShell';
import { useMerchantAnalytics, useMerchantLinks, useMerchantSettlements } from '../features/merchants/api';

type Section = 'overview' | 'payments' | 'links' | 'checkout' | 'qr' | 'refunds' | 'settlements' | 'analytics' | 'api-keys' | 'webhooks' | 'settings';
const merchantId = import.meta.env.VITE_DEMO_MERCHANT_ID as string | undefined;
const navigation: Array<{ key: Section; label: string; icon: typeof WalletCards; path: string }> = [
  { key: 'overview', label: 'Overview', icon: BarChart3, path: '/merchant' }, { key: 'payments', label: 'Payments', icon: CreditCard, path: '/merchant/payments' }, { key: 'links', label: 'Payment links', icon: Link2, path: '/merchant/payment-links' }, { key: 'checkout', label: 'Hosted checkout', icon: WalletCards, path: '/merchant/checkout' }, { key: 'qr', label: 'QR payments', icon: QrCode, path: '/merchant/qr' }, { key: 'refunds', label: 'Refunds', icon: RefreshCcw, path: '/merchant/refunds' }, { key: 'settlements', label: 'Settlements', icon: ClipboardList, path: '/merchant/settlements' }, { key: 'analytics', label: 'Analytics', icon: BarChart3, path: '/merchant/analytics' }, { key: 'api-keys', label: 'API keys', icon: Code2, path: '/merchant/api-keys' }, { key: 'webhooks', label: 'Webhooks', icon: Webhook, path: '/merchant/webhooks' }, { key: 'settings', label: 'Checkout settings', icon: Settings2, path: '/merchant/settings' },
];

function Metric({ label, value, note }: { label: string; value: string; note: string }) { return <div className="ledger-metric"><span>{label}</span><strong>{value}</strong><small>{note}</small></div>; }
function SectionNav({ active }: { active: Section }) { return <nav className="merchant-section-nav" aria-label="Merchant navigation">{navigation.map(({ key, label, icon: Icon, path }) => <Link className={active === key ? 'active' : ''} key={key} to={path}><Icon size={16}/><span>{label}</span></Link>)}</nav>; }

function MerchantPaymentPage({ section = 'payments' }: { section?: Section }) {
  const analytics = useMerchantAnalytics(merchantId);
  const links = useMerchantLinks(merchantId);
  const settlements = useMerchantSettlements(merchantId);
  const location = useLocation();
  const data = analytics.data?.data;
  const title = navigation.find((item) => item.key === section)?.label ?? 'Merchant workspace';
  const needsApi = !merchantId;
  const apiNote = needsApi ? 'Set VITE_DEMO_MERCHANT_ID after signing in to load merchant-scoped sandbox records.' : 'Data is loaded from the merchant-scoped sandbox API.';
  return <AppShell><main className="page merchant-page">
    <div className="page-heading"><div><span className="eyebrow">MERCHANT WORKSPACE</span><h1>{section === 'overview' ? 'Business control center' : title}</h1><p className="subtle">Payment acceptance, links, QR references, refunds, and settlement reporting for a sandbox merchant.</p></div><span className="demo-badge"><Sparkles size={13}/> Sandbox only</span></div>
    <div className="demo-notice"><ShieldCheck size={17}/><span><strong>Test mode.</strong> No live payment acceptance, provider settlement, raw card collection, or direct balance editing is enabled.</span></div>
    <SectionNav active={section}/>
    {(section === 'overview' || section === 'payments' || section === 'analytics') && <>
      <div className="ledger-overview-grid merchant-metrics"><Metric label="Total payments" value={data ? String(data.total_payments) : '—'} note={apiNote}/><Metric label="Successful" value={data ? String(data.successful_payments) : '—'} note="Verified sandbox payment intents"/><Metric label="Pending" value={data ? String(data.pending_payments) : '—'} note="No settlement implied"/><Metric label="Failed" value={data ? String(data.failed_payments) : '—'} note="Provider or validation result"/></div>
      <div className="merchant-columns"><section className="side-card"><div className="side-card-head"><div><span className="eyebrow">PAYMENT OPERATIONS</span><h2>{section === 'analytics' ? 'Accurate sandbox analytics' : 'Merchant payment activity'}</h2><p>{section === 'analytics' ? 'Aggregates are calculated from merchant-scoped database records; no fabricated activity is shown.' : 'Payment intents appear here only after the API returns authorized merchant-scoped records.'}</p></div><BarChart3 size={20}/></div><div className="empty-state compact"><span className="empty-icon"><CreditCard size={24}/></span><h2>{data?.total_payments ? 'Payment records available' : 'No payment records loaded'}</h2><p>{needsApi ? 'The console is ready for a configured merchant session.' : 'Create a sandbox checkout session to see verified activity here.'}</p></div></section><aside className="side-card"><span className="eyebrow">CURRENCY BREAKDOWN</span><h3>Gross volume</h3>{data ? Object.entries(data.gross_by_currency).map(([currency, amount]) => <div className="merchant-breakdown" key={currency}><span>{currency}</span><strong>{amount.toLocaleString()}</strong></div>) : <p className="subtle">No currency totals available.</p>}</aside></div>
    </>}
    {section === 'links' && <ResourcePanel title="Payment links" icon={Link2} loading={links.isLoading} error={links.isError} empty={!links.data?.data.length} copy="Create opaque, expiring links from the merchant API. Raw public tokens are returned only at creation." />}
    {section === 'checkout' && <ResourcePanel title="Hosted checkout" icon={WalletCards} copy="Checkout sessions can use provider-hosted collection when an approved adapter is configured. In this sandbox, wallet checkout is the only executable path." />}
    {section === 'qr' && <ResourcePanel title="QR payments" icon={QrCode} copy="Static and dynamic QR references are opaque and expiring. Generating a QR never marks a payment as complete." />}
    {section === 'refunds' && <ResourcePanel title="Refund workflow" icon={RefreshCcw} copy="Refunds require merchant authorization, an idempotency key, and compensating ledger entries. Over-refunds and direct balance edits are rejected." />}
    {section === 'settlements' && <><ResourcePanel title="Projected settlements" icon={ClipboardList} loading={settlements.isLoading} error={settlements.isError} empty={!settlements.data?.data.length} copy="Settlement rows are projections from sandbox payment and refund records. They are never marked settled without provider evidence." />{settlements.data?.data.map((row) => <div className="merchant-row" key={row.id}><strong>{row.currency_code}</strong><span>{row.status}</span><span>Net {row.net_amount_minor.toLocaleString()} minor units</span></div>)}</>}
    {section === 'api-keys' && <ResourcePanel title="Merchant API keys" icon={Code2} copy="Test keys are hashed at rest, scoped to one merchant, and shown in raw form only once at creation." />}
    {section === 'webhooks' && <ResourcePanel title="Merchant webhooks" icon={Webhook} copy="Webhook endpoints require HTTPS and public DNS. Signing secrets are encrypted, delivery is queued, and private-network destinations are rejected." />}
    {section === 'settings' && <ResourcePanel title="Checkout settings" icon={Settings2} copy="Configure branding, allowed currencies, payment methods, and redirect host allowlists through authorized server-side settings." />}
    <p className="merchant-route-note">Current route: {location.pathname} · all merchant actions remain sandbox-only.</p>
  </main></AppShell>;
}

function ResourcePanel({ title, icon: Icon, copy, empty = true, loading = false, error = false }: { title: string; icon: typeof WalletCards; copy: string; empty?: boolean; loading?: boolean; error?: boolean }) {
  return <section className="side-card merchant-resource"><div className="side-card-head"><div><span className="eyebrow">MERCHANT CONTROL</span><h2>{title}</h2><p>{copy}</p></div><Icon size={20}/></div>{loading ? <div className="merchant-loading">Loading merchant-scoped records…</div> : error ? <div className="merchant-error">Unable to load this sandbox resource. Check authentication and merchant membership.</div> : empty ? <div className="empty-state compact"><span className="empty-icon"><Icon size={24}/></span><h2>No records yet</h2><p>This view is ready for authorized sandbox data and does not invent payment activity.</p></div> : null}</section>;
}

export { MerchantPaymentPage };
