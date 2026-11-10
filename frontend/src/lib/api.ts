const configuredApiUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api/v1';

function resolveApiUrl(): string {
  const url = new URL(configuredApiUrl);

  // Keep local SPA and API cookies on the same hostname. This supports both
  // localhost and 127.0.0.1 without creating a cross-host Sanctum session.
  if (typeof window !== 'undefined') {
    const localHosts = new Set(['localhost', '127.0.0.1']);
    if (localHosts.has(url.hostname) && localHosts.has(window.location.hostname)) {
      url.hostname = window.location.hostname;
    }
  }

  return url.toString().replace(/\/$/, '');
}

const API_URL = resolveApiUrl();
const API_ORIGIN = new URL(API_URL).origin;
let csrfRequest: Promise<void> | null = null;

function readXsrfToken(): string | undefined {
  if (typeof document === 'undefined') return undefined;
  const value = document.cookie.split('; ').find((cookie) => cookie.startsWith('XSRF-TOKEN='));
  return value ? decodeURIComponent(value.slice('XSRF-TOKEN='.length)) : undefined;
}

async function ensureCsrfCookie(force = false): Promise<void> {
  if (!force && readXsrfToken()) return;
  if (!csrfRequest) {
    csrfRequest = fetch(`${API_ORIGIN}/sanctum/csrf-cookie`, {
      credentials: 'include',
      cache: 'no-store',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    }).then((response) => {
      if (!response.ok) throw new Error('Unable to initialize the secure session.');
    }).catch(() => {
      throw new Error('Unable to reach the Laravel API. Start the backend server and verify the API URL.');
    }).finally(() => {
      csrfRequest = null;
    });
  }
  await csrfRequest;
  if (!readXsrfToken()) throw new Error('Laravel did not return a CSRF cookie. Check the API hostname and cookie settings.');
}

export type ApiError = Error & { status?: number; payload?: { message?: string; mfa_required?: boolean; challenge_required?: boolean; enrollment_required?: boolean; email_verification_required?: boolean } };
export async function apiFetch<T>(path: string, init: RequestInit = {}): Promise<T> {
  const method = (init.method || 'GET').toUpperCase();
  if (!['GET', 'HEAD', 'OPTIONS'].includes(method)) await ensureCsrfCookie();
  const send = () => {
    const xsrfToken = readXsrfToken();
    return fetch(`${API_URL}${path}`, {
      ...init,
      credentials: 'include',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
        ...(init.headers || {}),
      },
    });
  };
  let response = await send();
  // Laravel rejects the request before controller execution on a 419, so a
  // single CSRF refresh/retry cannot duplicate a financial command.
  if (response.status === 419 && !['GET', 'HEAD', 'OPTIONS'].includes(method)) {
    await ensureCsrfCookie(true);
    response = await send();
  }
  if (!response.ok) {
    const payload = await response.json().catch(() => null);
    const error = new Error(payload?.message || 'Network request failed') as ApiError;
    error.status = response.status;
    error.payload = payload;
    throw error;
  }
  return response.json();
}
