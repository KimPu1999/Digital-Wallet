import { useEffect, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { Eye, EyeOff, LockKeyhole, ShieldCheck, WalletCards } from 'lucide-react';
import { apiFetch, type ApiError } from '../lib/api';

type Enrollment = { secret?: string; otpauth_url?: string };

export function PlatformAdminLoginPage() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [code, setCode] = useState('');
  const [enrollment, setEnrollment] = useState<Enrollment | null>(null);
  const [challenge, setChallenge] = useState(false);
  const [busy, setBusy] = useState(false);
  const [resending, setResending] = useState(false);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');

  useEffect(() => {
    if (searchParams.get('verified') === '1') setNotice('Administrator email verified. You can now sign in and complete MFA.');
    if (searchParams.get('verification') === 'invalid') setError('This verification link is invalid or expired. Request a new verification email.');
  }, [searchParams]);

  async function signIn() {
    setBusy(true); setError(''); setNotice(''); setChallenge(false);
    try {
      await apiFetch('/auth/admin/login', { method: 'POST', body: JSON.stringify({ email, password }) });
      navigate('/admin');
    } catch (cause) {
      const apiError = cause as ApiError;
      if (apiError.payload?.enrollment_required) {
        setNotice('Your email is verified. Complete MFA enrollment before platform access.');
        try { const result = await apiFetch<{ data: Enrollment }>('/auth/mfa/enroll', { method: 'POST' }); setEnrollment(result.data); } catch (enrollmentError) { setError((enrollmentError as Error).message); }
      } else if (apiError.payload?.challenge_required) {
        setChallenge(true);
        setNotice('Enter the current six-digit code from your authenticator app.');
      } else if (apiError.payload?.email_verification_required) {
        setError('Verify this administrator email from the signed verification message before signing in.');
      } else { setError(apiError.message || 'Unable to sign in.'); }
    } finally { setBusy(false); }
  }

  async function verifyMfa() {
    setBusy(true); setError('');
    try {
      if (challenge) {
        await apiFetch('/auth/admin/mfa/verify', { method: 'POST', body: JSON.stringify({ code }) });
        navigate('/admin');
      } else {
        await apiFetch('/auth/mfa/verify', { method: 'POST', body: JSON.stringify({ code }) });
        setEnrollment(null); setCode('');
        await signIn();
      }
    } catch (cause) { setError((cause as Error).message || 'MFA verification failed.'); setBusy(false); }
  }

  async function resendVerification() {
    setResending(true); setError('');
    try {
      await apiFetch('/auth/admin/email/resend', { method: 'POST', body: JSON.stringify({ email }) });
      setNotice('If this is an eligible administrator account, a new verification message has been sent.');
    } catch (cause) { setError((cause as Error).message || 'Unable to request verification.'); }
    finally { setResending(false); }
  }

  return <div className="auth-page"><div className="auth-brand"><Link to="/" className="brand"><span className="brand-mark"><WalletCards size={19} /></span><span>moneta<span className="brand-dot">.</span></span></Link><Link to="/" className="back-link">Back to home</Link></div><div className="auth-card"><span className="eyebrow">PLATFORM CONTROL CENTER</span><h1>Administrator sign in.</h1><p className="subtle">This protected entry point is separate from customer and merchant authentication.</p>{error && <div className="auth-error" role="alert">{error}</div>}{notice && <div className="auth-note" role="status"><ShieldCheck size={15} />{notice}</div>}{!enrollment && !challenge ? <form onSubmit={(event) => { event.preventDefault(); void signIn(); }}><label>Email address<input type="email" value={email} onChange={(event) => setEmail(event.target.value)} autoComplete="username" required /></label><label>Password<span className="password-field"><input type={showPassword ? 'text' : 'password'} value={password} onChange={(event) => setPassword(event.target.value)} autoComplete="current-password" required /><button type="button" className="password-toggle" onClick={() => setShowPassword((visible) => !visible)} aria-label={showPassword ? 'Hide password' : 'Show password'} aria-pressed={showPassword}>{showPassword ? <EyeOff size={16}/> : <Eye size={16}/>}</button></span></label>{error.toLowerCase().includes('verification') && <button type="button" className="secondary-btn full" onClick={() => void resendVerification()} disabled={resending}>{resending ? 'Sending…' : 'Resend verification email'}</button>}<button className="primary-btn full" disabled={busy}>{busy ? 'Checking…' : 'Continue'} <LockKeyhole size={16} /></button></form> : <form onSubmit={(event) => { event.preventDefault(); void verifyMfa(); }}>{challenge ? <div className="auth-note"><ShieldCheck size={15} />MFA challenge is required for this administrator session.</div> : <><div className="auth-note"><ShieldCheck size={15} />Use an authenticator app to scan the enrollment URI, then enter the six-digit code.</div><label>Enrollment URI<input value={enrollment?.otpauth_url || enrollment?.secret || ''} readOnly aria-label="MFA enrollment URI" /></label></>}<label>Authenticator code<input inputMode="numeric" pattern="[0-9]{6}" maxLength={6} value={code} onChange={(event) => setCode(event.target.value)} autoComplete="one-time-code" required /></label><button className="primary-btn full" disabled={busy}>{busy ? 'Verifying…' : challenge ? 'Verify and open Control Center' : 'Verify MFA'} <ShieldCheck size={16} /></button></form>}<p className="auth-note"><LockKeyhole size={15} />Admin access requires an active platform membership, verified email, MFA, and server-side permission checks.</p></div></div>;
}
