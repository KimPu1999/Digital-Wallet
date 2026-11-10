import { useEffect, useState, type ReactNode } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Bell, ChevronDown, Globe2, HelpCircle, Home, LogOut, Menu, Moon, MoveRight, PanelLeftClose, PanelLeftOpen, ReceiptText, ScanLine, Settings, ShieldCheck, Sun, Users, WalletCards, X, ArrowDownLeft, ArrowUpRight, CreditCard, RefreshCw } from 'lucide-react';
import { Link, NavLink, useLocation, useNavigate } from 'react-router-dom';
import { apiFetch } from '../../lib/api';
import { useLanguage } from '../../hooks/useLanguage';

type Theme = 'light' | 'dark';
type NotificationRecord = { id: string; read_at?: string | null; data?: { title?: string; body?: string }; type?: string; created_at?: string };

const navigation = [
  { to: '/app', label: 'Overview', icon: Home },
  { to: '/app/wallets', label: 'Wallets', icon: WalletCards },
  { to: '/app/payments', label: 'Payments', icon: CreditCard },
  { to: '/app/transfers', label: 'Send Money', icon: ArrowUpRight },
  { to: '/app/fx', label: 'Exchange', icon: RefreshCw },
  { to: '/app/beneficiaries', label: 'People', icon: Users },
  { to: '/app/transactions', label: 'Transaction History', icon: ReceiptText },
];

function useCustomerTheme() {
  const [theme, setTheme] = useState<Theme>(() => (localStorage.getItem('dw-theme') as Theme) || 'light');
  const toggle = () => setTheme((value) => value === 'light' ? 'dark' : 'light');
  useEffect(() => { document.documentElement.dataset.theme = theme; localStorage.setItem('dw-theme', theme); }, [theme]);
  return { theme, toggle };
}

export function CustomerLayout({ children }: { children: ReactNode }) {
  const [mobileOpen, setMobileOpen] = useState(false);
  const [collapsed, setCollapsed] = useState(() => localStorage.getItem('dw-customer-sidebar') === 'collapsed');
  const [notificationsOpen, setNotificationsOpen] = useState(false);
  const [profileOpen, setProfileOpen] = useState(false);
  const [logoutOpen, setLogoutOpen] = useState(false);
  const { language, toggleLanguage } = useLanguage();
  const { theme, toggle: toggleTheme } = useCustomerTheme();
  const location = useLocation();
  const navigate = useNavigate();
  const client = useQueryClient();
  const unread = useQuery({ queryKey: ['customer-notification-unread'], queryFn: () => apiFetch<{ data: { unread: number } }>('/notifications/unread-count') });
  const notifications = useQuery({ queryKey: ['customer-notification-preview'], queryFn: () => apiFetch<{ data: NotificationRecord[] }>('/notifications?per_page=5') });
  const logout = useMutation({ mutationFn: () => apiFetch('/auth/logout', { method: 'POST' }), onSettled: () => navigate('/login') });
  const markRead = useMutation({ mutationFn: (id: string) => apiFetch(`/notifications/${id}/read`, { method: 'PATCH' }), onSuccess: () => { client.invalidateQueries({ queryKey: ['customer-notification-unread'] }); client.invalidateQueries({ queryKey: ['customer-notification-preview'] }); } });
  const title = location.pathname === '/app' ? 'Overview' : navigation.find((item) => location.pathname.startsWith(item.to) && item.to !== '/app')?.label || 'Personal wallet';
  const toggleCollapsed = () => { const next = !collapsed; setCollapsed(next); localStorage.setItem('dw-customer-sidebar', next ? 'collapsed' : 'expanded'); };

  return <div className={`customer-shell ${collapsed ? 'customer-shell-collapsed' : ''}`}>
    <div className={`customer-overlay ${mobileOpen ? 'visible' : ''}`} onClick={() => setMobileOpen(false)} aria-hidden="true" />
    <aside className={`customer-sidebar ${mobileOpen ? 'mobile-open' : ''}`} aria-label="Personal Wallet navigation">
      <div className="customer-brand-row"><Link to="/app" className="customer-brand" onClick={() => setMobileOpen(false)}><span className="customer-brand-mark"><WalletCards size={19} /></span><span className="customer-brand-copy">moneta<span>.</span><small>Personal Wallet</small></span></Link><button className="customer-icon-button customer-mobile-close" onClick={() => setMobileOpen(false)} aria-label="Close navigation"><X size={19} /></button></div>
      <button className="customer-collapse" onClick={toggleCollapsed} aria-label={collapsed ? 'Expand navigation' : 'Collapse navigation'} title={collapsed ? 'Expand navigation' : 'Collapse navigation'}>{collapsed ? <PanelLeftOpen size={18} /> : <PanelLeftClose size={18} />}<span>{collapsed ? 'Expand' : 'Collapse sidebar'}</span></button>
      <nav className="customer-nav" aria-label="Wallet navigation">{navigation.map(({ to, label, icon: Icon }) => <NavLink key={to} to={to} end={to === '/app'} onClick={() => setMobileOpen(false)} title={label}><Icon size={18} /><span>{label}</span></NavLink>)}</nav>
      <div className="customer-nav-divider" />
      <nav className="customer-nav" aria-label="Account navigation"><NavLink to="/app/security" onClick={() => setMobileOpen(false)} title="Security"><ShieldCheck size={18} /><span>Security</span></NavLink><NavLink to="/app/settings" onClick={() => setMobileOpen(false)} title="Settings"><Settings size={18} /><span>Settings</span></NavLink><NavLink to="/app/support" onClick={() => setMobileOpen(false)} title="Help center"><HelpCircle size={18} /><span>Help center</span></NavLink></nav>
      <div className="customer-sidebar-spacer" />
      <div className="customer-sandbox-card"><span className="customer-live-dot" /><div><strong>Sandbox environment</strong><small>Live financial actions are disabled</small></div></div>
      <button className="customer-signout" onClick={() => setLogoutOpen(true)} title="Sign out"><LogOut size={17} /><span>Sign out</span></button>
    </aside>
    <div className="customer-main">
      <header className="customer-topbar"><button className="customer-icon-button customer-menu-button" onClick={() => setMobileOpen(true)} aria-label="Open navigation"><Menu size={20} /></button><div className="customer-breadcrumb"><span className="customer-topbar-kicker">PERSONAL WALLET</span><strong>{title}</strong></div><div className="customer-topbar-actions"><div className="customer-search"><ScanLine size={16} /><input aria-label="Search this wallet" placeholder="Search" /></div><button className="customer-lang-button" onClick={toggleLanguage} aria-label="Switch language"><Globe2 size={16} />{language.toUpperCase()}</button><button className="customer-icon-button" onClick={toggleTheme} aria-label="Toggle theme">{theme === 'light' ? <Moon size={18} /> : <Sun size={18} />}</button><div className="customer-popover-wrap"><button className="customer-icon-button customer-notification-button" onClick={() => { setNotificationsOpen((value) => !value); setProfileOpen(false); }} aria-label="Open notifications" aria-expanded={notificationsOpen}><Bell size={18} />{(unread.data?.data.unread ?? 0) > 0 && <span>{unread.data?.data.unread}</span>}</button>{notificationsOpen && <div className="customer-popover customer-notification-popover"><div className="customer-popover-head"><div><strong>Notifications</strong><small>{unread.data?.data.unread ?? 0} unread</small></div><Link to="/app/notifications" onClick={() => setNotificationsOpen(false)}>View all</Link></div>{notifications.isLoading && <div className="customer-popover-empty">Loading updates…</div>}{notifications.isError && <div className="customer-popover-empty">Notifications are unavailable right now.</div>}{!notifications.isLoading && !notifications.isError && !(notifications.data?.data?.length) && <div className="customer-popover-empty">You’re all caught up.</div>}{notifications.data?.data?.slice(0, 5).map((item) => <button className={`customer-notification-item ${item.read_at ? '' : 'unread'}`} key={item.id} onClick={() => { if (!item.read_at) markRead.mutate(item.id); }}><span className="customer-notification-dot" /><span><strong>{item.data?.title || item.type || 'Account update'}</strong><small>{item.data?.body || 'A new account update is available.'}</small></span></button>)}</div>}</div><div className="customer-profile-wrap"><button className="customer-profile-button" onClick={() => { setProfileOpen((value) => !value); setNotificationsOpen(false); }} aria-expanded={profileOpen}><span className="customer-avatar">PW</span><span className="customer-profile-copy"><strong>Personal wallet</strong><small>Sandbox account</small></span><ChevronDown size={16} /></button>{profileOpen && <div className="customer-popover customer-profile-popover"><Link to="/app/security" onClick={() => setProfileOpen(false)}><ShieldCheck size={16} /> Security center</Link><Link to="/app/settings" onClick={() => setProfileOpen(false)}><Settings size={16} /> Account settings</Link><button onClick={() => { setProfileOpen(false); setLogoutOpen(true); }}><LogOut size={16} /> Sign out</button></div>}</div></div></header>
      <div className="customer-content">{children}</div>
      <nav className="customer-mobile-nav" aria-label="Mobile navigation">{navigation.slice(0, 5).map(({ to, label, icon: Icon }) => <NavLink key={to} to={to} end={to === '/app'}><Icon size={18} /><span>{label === 'Transaction History' ? 'Activity' : label.split(' ')[0]}</span></NavLink>)}</nav>
    </div>
    {logoutOpen && <div className="customer-dialog-backdrop" role="presentation"><div className="customer-dialog" role="dialog" aria-modal="true" aria-labelledby="logout-title"><div className="customer-dialog-icon"><LogOut size={20} /></div><h2 id="logout-title">Sign out of Moneta?</h2><p>Your current session will be closed securely.</p><div className="customer-dialog-actions"><button className="customer-secondary-button" onClick={() => setLogoutOpen(false)}>Cancel</button><button className="customer-danger-button" onClick={() => logout.mutate()} disabled={logout.isPending}>{logout.isPending ? 'Signing out…' : 'Sign out'}<MoveRight size={16} /></button></div></div></div>}
  </div>;
}
