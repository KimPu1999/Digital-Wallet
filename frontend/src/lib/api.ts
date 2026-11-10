const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1';
export type ApiError = Error & { status?: number; payload?: { message?: string; mfa_required?: boolean; enrollment_required?: boolean; email_verification_required?: boolean } };
export async function apiFetch<T>(path: string, init: RequestInit = {}): Promise<T> {
  const response = await fetch(`${API_URL}${path}`, { ...init, credentials: 'include', headers: { 'Content-Type': 'application/json', ...(init.headers || {}) } });
  if (!response.ok) {
    const payload = await response.json().catch(() => null);
    const error = new Error(payload?.message || 'Network request failed') as ApiError;
    error.status = response.status;
    error.payload = payload;
    throw error;
  }
  return response.json();
}
