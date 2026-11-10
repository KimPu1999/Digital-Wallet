export type ApiEnvelope<T> = { data: T; message?: string };
export type Currency = { code: string; name: string; symbol: string; decimals: number; display_order: number; is_active: boolean };
export type WalletCurrencyAccount = { id: string; status: string; currency: Currency; balance: number | null; balance_label: string };
export type Wallet = { id: string; label: string; nickname?: string | null; status: 'active' | 'disabled' | 'pending_review' | 'restricted'; wallet_type: 'customer' | 'merchant'; owner: { id: string; type: string; user_id: string; merchant_id?: string | null }; currency_accounts: WalletCurrencyAccount[]; financial_data: { available_balance: number | null; pending_balance: number | null; source_of_truth: string } };
