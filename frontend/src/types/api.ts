export type ApiEnvelope<T> = { data: T; message?: string };
export type Wallet = { id: string; label: string; status: 'active' | 'disabled'; wallet_type: 'customer' | 'merchant'; currency: { code: string; name: string } };
