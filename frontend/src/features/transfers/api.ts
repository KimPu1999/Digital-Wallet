import { useMutation, useQuery } from '@tanstack/react-query';
import { apiFetch } from '../../lib/api';

export type Beneficiary = { id: string; display_name: string; beneficiary_type: string; country_code?: string | null; status: string; verification_status: string; accounts?: Array<{ id: string; account_type: string; currency_code?: string | null; account_last4?: string | null; status: string }> };
export type TransferQuote = { id: string; quote_reference: string; source_currency_code: string; destination_currency_code: string; source_amount_minor: number; recipient_amount_minor: number; platform_fee_minor: number; exchange_rate: string; expires_at: string; is_expired: boolean; sandbox_only: boolean };
export type Transfer = { id: string; transfer_reference: string; source_currency_code: string; destination_currency_code: string; source_amount_minor: number; recipient_amount_minor: number; status: string; sandbox_only: boolean; beneficiary?: { display_name: string } };

export function useBeneficiaries() { return useQuery({ queryKey: ['beneficiaries'], queryFn: () => apiFetch<{ data: Beneficiary[] }>('/beneficiaries') }); }
export function useTransfers() { return useQuery({ queryKey: ['transfers'], queryFn: () => apiFetch<{ data: Transfer[] }>('/transfers') }); }
export function useCreateBeneficiary() { return useMutation({ mutationFn: (body: { display_name: string; beneficiary_type: 'internal_wallet' | 'bank_account' | 'payout_provider'; country_code: string; currency_code: string; account_details: Record<string, string> }) => apiFetch<{ data: Beneficiary }>('/beneficiaries', { method: 'POST', body: JSON.stringify(body) }) }); }
export function useTransferQuote() { return useMutation({ mutationFn: (body: { source_wallet_currency_account_id: string; beneficiary_id: string; beneficiary_account_id: string; source_amount_minor: number }) => apiFetch<{ data: TransferQuote }>('/transfers/quotes', { method: 'POST', body: JSON.stringify(body) }) }); }
export function useStepUp() { return useMutation({ mutationFn: () => apiFetch<{ data: { challenge_id: string; sandbox_code: string } }>('/transfers/step-up', { method: 'POST', body: '{}' }) }); }
export function useExecuteTransfer() { return useMutation({ mutationFn: (body: { quote_id: string; challenge_id: string; challenge_code: string }) => apiFetch<{ data: Transfer }>('/transfers', { method: 'POST', headers: { 'Idempotency-Key': crypto.randomUUID() }, body: JSON.stringify(body) }) }); }
