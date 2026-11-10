import { useQuery } from '@tanstack/react-query';
import { apiFetch } from '../../lib/api';

export type MerchantAnalytics = { total_payments: number; successful_payments: number; pending_payments: number; failed_payments: number; gross_by_currency: Record<string, number>; sandbox_only: boolean };
export type MerchantLink = { id: string; amount_minor: number | null; currency_code: string; description?: string | null; status: string; expires_at?: string | null; usage_count: number; usage_limit?: number | null; single_use: boolean; sandbox_only: boolean };
export type MerchantSettlement = { id: string; settlement_reference: string; currency_code: string; gross_amount_minor: number; fee_amount_minor: number; refund_amount_minor: number; net_amount_minor: number; status: string; period_start: string; period_end: string; sandbox_only: boolean };
type ListResponse<T> = { data: T[]; meta?: { current_page: number; last_page: number; total: number }; sandbox_only: boolean };

export function useMerchantAnalytics(merchantId?: string) { return useQuery({ queryKey: ['merchant-analytics', merchantId], enabled: Boolean(merchantId), queryFn: () => apiFetch<{ data: MerchantAnalytics }>('/merchants/' + merchantId + '/analytics') }); }
export function useMerchantLinks(merchantId?: string) { return useQuery({ queryKey: ['merchant-links', merchantId], enabled: Boolean(merchantId), queryFn: () => apiFetch<ListResponse<MerchantLink>>('/merchants/' + merchantId + '/payment-links') }); }
export function useMerchantSettlements(merchantId?: string) { return useQuery({ queryKey: ['merchant-settlements', merchantId], enabled: Boolean(merchantId), queryFn: () => apiFetch<{ data: MerchantSettlement[] }>('/merchants/' + merchantId + '/settlements') }); }
