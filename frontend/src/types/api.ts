export type ApiEnvelope<T> = { data: T; message?: string };
export type Currency = { code: string; name: string; symbol: string; decimals: number; display_order: number; is_active: boolean };
export type WalletCurrencyAccount = { id: string; status: string; currency: Currency; balance: number | null; balance_label: string };
export type Wallet = { id: string; label: string; nickname?: string | null; status: 'active' | 'disabled' | 'pending_review' | 'restricted'; wallet_type: 'customer' | 'merchant'; owner: { id: string; type: string; user_id: string; merchant_id?: string | null }; currency_accounts: WalletCurrencyAccount[]; financial_data: { available_balance: number | null; pending_balance: number | null; source_of_truth: string } };
export type LedgerBalance = { currency_code: string; posted_minor: number; held_minor: number; available_minor: number; pending_minor: number; projection_version: number; source_of_truth: 'double-entry ledger' };
export type WalletBalance = { wallet_id: string; accounts: Array<{ currency_code: string; currency_name: string; account_status: string; balance: LedgerBalance }>; ledger_source_of_truth: boolean; sandbox_only: boolean };
export type LedgerEntry = { id: string; currency_code: string; side: 'debit' | 'credit'; amount_minor: number; sequence: number; account_code?: string };
export type LedgerActivity = { id: string; reference: string; transaction_type: string; status: string; description?: string | null; posted_at?: string | null; entries?: LedgerEntry[]; sandbox_only: boolean };
