import { useMutation, useQuery } from '@tanstack/react-query';
import { apiFetch } from '../../lib/api';

export type PaymentIntent = {
  id: string;
  intent_reference: string;
  operation: 'deposit' | 'withdrawal' | 'merchant_payment';
  payment_method: string;
  currency_code: string;
  amount_minor: number;
  fee_minor: number;
  status: string;
  failure_reason?: string | null;
  external_reference?: string | null;
  settlement_reference?: string | null;
  sandbox_only: boolean;
  provider?: { code: string; name: string };
};

type Paged<T> = { data: T[]; meta?: { current_page: number; last_page: number; total: number }; sandbox_only: boolean };

export function usePayments(operation?: string) {
  const query = operation ? '?operation=' + encodeURIComponent(operation) : '';
  return useQuery({ queryKey: ['payments', operation], queryFn: () => apiFetch<Paged<PaymentIntent>>('/payments' + query) });
}

export function useCreateDeposit() {
  return useMutation({
    mutationFn: (payload: { wallet_currency_account_id: string; amount_minor: number; payment_method: string; provider_scenario?: string }) =>
      apiFetch<{ data: PaymentIntent; sandbox_only: boolean }>('/payments/deposits/intent', { method: 'POST', headers: { 'Idempotency-Key': 'deposit-' + crypto.randomUUID() }, body: JSON.stringify(payload) }),
  });
}

export function useCreateWithdrawal() {
  return useMutation({
    mutationFn: (payload: { wallet_currency_account_id: string; amount_minor: number; payment_method: string; challenge_id: string; challenge_code: string; provider_scenario?: string }) =>
      apiFetch<{ data: PaymentIntent; sandbox_only: boolean }>('/payments/withdrawals/intent', { method: 'POST', headers: { 'Idempotency-Key': 'withdrawal-' + crypto.randomUUID() }, body: JSON.stringify(payload) }),
  });
}

export function usePaymentMethods() {
  return useQuery({ queryKey: ['payment-methods'], queryFn: () => apiFetch<{ data: { code: string; sandbox_only: boolean }[] }>('/payment-methods') });
}
