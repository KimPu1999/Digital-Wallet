import { Building2, Eye, LockKeyhole, ShieldCheck, Users, WalletCards } from 'lucide-react';
import { Link } from 'react-router-dom';
import { AppShell } from '../components/layout/AppShell';
import { CurrencyWalletCard, DemoLabel, WalletStatusBadge, type WalletCurrency } from '../features/wallet/components';

const currencies: WalletCurrency[] = [
  { code: 'USD', name: 'US Dollar', symbol: '$', exponent: 2 },
  { code: 'SGD', name: 'Singapore Dollar', symbol: 'S$', exponent: 2 },
  { code: 'MMK', name: 'Myanmar Kyat', symbol: 'K', exponent: 0 },
];

export function MerchantWalletPage() {
  return <AppShell><main className="page">
    <div className="page-heading"><div><span className="eyebrow">MERCHANT WORKSPACE</span><h1>Business wallet</h1><p className="subtle">Merchant-scoped currency accounts for a sandbox business.</p></div><DemoLabel /></div>
    <div className="wallet-demo-banner"><Building2 size={16}/><span><strong>Read-only demo</strong> Payment requests, QR payments, and settlement are unavailable until a future ledger and compliance review.</span></div>
    <div className="wallet-management-grid"><div>
      <div className="wallet-card-grid">{currencies.map(currency => <CurrencyWalletCard key={currency.code} account={{ id: `merchant-${currency.code}`, status: 'active', currency, balance: null, balance_label: 'Phase 4 ledger' }} />)}</div>
      <div className="section-heading"><div><h2>Business controls</h2><p>Team permissions and verification status remain server-authorized.</p></div></div>
      <div className="actions-grid"><div className="wallet-info-card"><Users size={20}/><strong>Team management</strong><span>Membership-scoped access checks</span></div><div className="wallet-info-card"><ShieldCheck size={20}/><strong>Business verification</strong><span>Review status: pending</span></div><div className="wallet-info-card"><LockKeyhole size={20}/><strong>Payment controls</strong><span>Coming in a later phase</span></div></div>
    </div><aside className="wallet-side-column"><div className="side-card"><span className="eyebrow">BUSINESS STATUS</span><h3>Sandbox merchant</h3><p>Currency configuration is not approval for a payment corridor or live processing.</p><WalletStatusBadge status="pending"/></div><div className="side-card"><span className="eyebrow">NEXT STEP</span><h3>Ledger readiness</h3><p>Phase 4 must define settlement, posting, reconciliation, and idempotency before activation.</p><Link className="secondary-btn" to="/app/wallets">View customer wallets <Eye size={15}/></Link></div></aside></div>
  </main></AppShell>;
}

export function AdminWalletMonitoringPage() {
  return <AppShell><main className="page">
    <div className="page-heading"><div><span className="eyebrow">PLATFORM CONTROL CENTER</span><h1>Wallet monitoring</h1><p className="subtle">Read-only operational visibility for authorized platform staff.</p></div><DemoLabel /></div>
    <div className="wallet-demo-banner"><WalletCards size={16}/><span><strong>Protected admin view</strong> This screen does not expose or modify balances. Access is enforced by Laravel role middleware.</span></div>
    <div className="wallet-management-grid"><div><div className="wallet-table-card"><div className="wallet-table-head"><div><h2>Wallet directory</h2><p>Demo records only · pagination is capped at 10 rows</p></div><span className="count-badge">3 demo</span></div><div className="wallet-table-row wallet-table-header"><span>Owner</span><span>Currency</span><span>Status</span><span>Access</span></div>{['Customer wallet · Kai Lin','Merchant wallet · Moneta Cafe','Customer wallet · Aye Aye'].map((owner,index)=><div className="wallet-table-row" key={owner}><span><strong>{owner}</strong><small>Owner identity is server-scoped</small></span><span>{['USD','SGD','MMK'][index]}</span><span><WalletStatusBadge status={index === 1 ? 'pending' : 'active'}/></span><span><Eye size={16}/> Read only</span></div>)}</div></div><aside className="wallet-side-column"><div className="side-card"><span className="eyebrow">AUDIT COVERAGE</span><h3>Status history enabled</h3><p>Wallet creation and status transitions generate audit events with actor context.</p><ShieldCheck size={22}/></div><div className="side-card"><span className="eyebrow">RESTRICTION</span><h3>No manual balance changes</h3><p>There are no balance mutation endpoints in Phase 3.</p></div></aside></div>
  </main></AppShell>;
}
