import { useQuery } from '@tanstack/react-query';
import { apiFetch } from '../../lib/api';
import type { ApiEnvelope, LedgerActivity, WalletBalance } from '../../types/api';

export function useWalletBalance(walletId?: string) {
  return useQuery({ queryKey: ['wallet-balance', walletId], queryFn: () => apiFetch<ApiEnvelope<WalletBalance>>(`/wallets/${walletId}/balance`), enabled: Boolean(walletId), staleTime: 15_000 });
}

export function useWalletLedgerActivity(walletId?: string) {
  return useQuery({ queryKey: ['wallet-ledger-activity', walletId], queryFn: () => apiFetch<{ data: LedgerActivity[]; meta: Record<string, number> }>(`/wallets/${walletId}/ledger-activity`), enabled: Boolean(walletId), staleTime: 15_000 });
}
