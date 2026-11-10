import { useMutation, useQuery } from '@tanstack/react-query';
import { apiFetch } from '../../lib/api';

export type FxPair = { id: string; base_currency_code: string; quote_currency_code: string; min_amount_minor: number; max_amount_minor: number | null; quote_expiry_seconds: number };
export type FxQuote = { id: string; quote_reference: string; source_currency_code: string; target_currency_code: string; source_amount_minor: number; estimated_target_amount_minor: number; platform_fee_minor: number; mid_market_rate: string; exchange_rate: string; provider_spread_bps: number; platform_markup_bps: number; expires_at: string; is_expired: boolean; sandbox_only: boolean };
export type FxConversion = { id: string; quote_id: string; source_currency_code: string; target_currency_code: string; source_amount_minor: number; target_amount_minor: number; fee_minor: number; exchange_rate: string; status: string; sandbox_only: boolean };

export function useFxPairs() { return useQuery({ queryKey: ['fx-pairs'], queryFn: () => apiFetch<{ data: FxPair[] }>('/fx/pairs') }); }
export function useCreateFxQuote() { return useMutation({ mutationFn: (body: { source_wallet_currency_account_id: string; target_wallet_currency_account_id: string; source_amount_minor: number }) => apiFetch<{ data: FxQuote }>('/fx/quotes', { method: 'POST', body: JSON.stringify(body) }) }); }
export function useExecuteFxConversion() { return useMutation({ mutationFn: ({ quoteId, idempotencyKey }: { quoteId: string; idempotencyKey: string }) => apiFetch<{ data: FxConversion }>(`/fx/quotes/${quoteId}/convert`, { method: 'POST', headers: { 'Idempotency-Key': idempotencyKey }, body: '{}' }) }); }
