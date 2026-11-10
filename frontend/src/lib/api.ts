const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1';
const API_ORIGIN = new URL(API_URL).origin;
let csrfRequest: Promise<void> | null = null;

function readXsrfToken(): string | undefined {
  if (typeof document === 'undefined') return undefined;
  const value = document.cookie.split('; ').find((cookie) => cookie.startsWith('XSRF-TOKEN='));
  return value ? decodeURIComponent(value.slice('XSRF-TOKEN='.length)) : undefined;
}

async function ensureCsrfCookie(): Promise<void> {
  if (!csrfRequest) {
    csrfRequest = fetch(`${API_ORIGIN}/sanctum/csrf-cookie`, {
      credentials: 'include',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    }).then((response) => {
      if (!response.ok) throw new Error('Unable to initialize the secure session.');
    }).catch((error) => {
      csrfRequest = null;
      throw error;
    });
  }
  await csrfRequest;
}

export type ApiError = Error & { status?: number; payload?: { message?: string; mfa_required?: boolean; enrollment_required?: boolean; email_verification_required?: boolean } };
export async function apiFetch<T>(path: string, init: RequestInit = {}): Promise<T> {
  const method = (init.method || 'GET').toUpperCase();
  if (!['GET', 'HEAD', 'OPTIONS'].includes(method)) await ensureCsrfCookie();
  const xsrfToken = readXsrfToken();
  const response = await fetch(`${API_URL}${path}`, {
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
  if (!response.ok) {
    const payload = await response.json().catch(() => null);
    const error = new Error(payload?.message || 'Network request failed') as ApiError;
    error.status = response.status;
    error.payload = payload;
    throw error;
  }
  return response.json();
}
