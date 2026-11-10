import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { LockKeyhole, ShieldCheck, WalletCards } from 'lucide-react';
import { apiFetch, type ApiError } from '../lib/api';

type Enrollment = { secret?: string; otpauth_url?: string };

export function PlatformAdminLoginPage() {
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [code, setCode] = useState('');
  const [enrollment, setEnrollment] = useState<Enrollment | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');

  async function signIn() {
    setBusy(true); setError(''); setNotice('');
    try {
      await apiFetch('/auth/admin/login', { method: 'POST', body: JSON.stringify({ email, password }) });
      navigate('/admin');
    } catch (cause) {
      const apiError = cause as ApiError;
      if (apiError.payload?.enrollment_required) {
        setNotice('Your email is verified. Complete MFA enrollment before platform access.');
        try { const result = await apiFetch<{ data: Enrollment }>('/auth/mfa/enroll', { method: 'POST' }); setEnrollment(result.data); } catch (enrollmentError) { setError((enrollmentError as Error).message); }
      } else if (apiError.payload?.email_verification_required) {
        setError('Verify this administrator email from the signed verification message before signing in.');
      } else { setError(apiError.message); }
    } finally { setBusy(false); }
  }

  async function verifyMfa() {
    setBusy(true); setError('');
    try { await apiFetch('/auth/mfa/verify', { method: 'POST', body: JSON.stringify({ code }) }); await signIn(); }
    catch (cause) { setError((cause as Error).message); setBusy(false); }
  }

  return <div className="auth-page"><div className="auth-brand"><Link to="/" className="brand"><span className="brand-mark"><WalletCards size={19} /></span><span>moneta<span className="brand-dot">.</span></span></Link><Link to="/" className="back-link">Back to home</Link></div><div className="auth-card"><span className="eyebrow">PLATFORM CONTROL CENTER</span><h1>Administrator sign in.</h1><p className="subtle">This protected entry point is separate from customer and merchant authentication.</p>{error && <div className="auth-error" role="alert">{error}</div>}{notice && <div className="auth-note" role="status"><ShieldCheck size={15} />{notice}</div>}{!enrollment ? <form onSubmit={(event) => { event.preventDefault(); void signIn(); }}><label>Email address<input type="email" value={email} onChange={(event) => setEmail(event.target.value)} autoComplete="username" required /></label><label>Password<input type="password" value={password} onChange={(event) => setPassword(event.target.value)} autoComplete="current-password" required /></label><button className="primary-btn full" disabled={busy}>{busy ? 'Checking…' : 'Continue'} <LockKeyhole size={16} /></button></form> : <form onSubmit={(event) => { event.preventDefault(); void verifyMfa(); }}><div className="auth-note"><ShieldCheck size={15} />Use an authenticator app to scan the enrollment URI, then enter the six-digit code.</div><label>Enrollment URI<input value={enrollment.otpauth_url || enrollment.secret || ''} readOnly aria-label="MFA enrollment URI" /></label><label>Authenticator code<input inputMode="numeric" pattern="[0-9]{6}" value={code} onChange={(event) => setCode(event.target.value)} autoComplete="one-time-code" required /></label><button className="primary-btn full" disabled={busy}>{busy ? 'Verifying…' : 'Verify MFA'} <ShieldCheck size={16} /></button></form>}<p className="auth-note"><LockKeyhole size={15} />Admin access requires an active platform membership, verified email, MFA, and server-side permission checks.</p></div></div>;
}
