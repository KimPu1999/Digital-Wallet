import { zodResolver } from '@hookform/resolvers/zod';
import { AlertTriangle, ArrowDownLeft, ArrowUpRight, Clock3, CreditCard, LockKeyhole, Sparkles } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { useCreateDeposit, useCreateWithdrawal, usePaymentMethods, usePayments, type PaymentIntent } from '../features/payments/api';
import { useStepUp } from '../features/transfers/api';

const schema = z.object({ wallet_currency_account_id: z.string().uuid('Use the wallet currency account UUID.'), amount_minor: z.coerce.number().int().positive(), payment_method: z.string().min(1), provider_scenario: z.enum(['processing', 'failed', 'unknown']).default('processing') });
type FormValues = z.infer<typeof schema>;

function Status({ status }: { status: string }) {
  return <span className={['wallet-status', status === 'succeeded' ? 'success' : status === 'failed' ? 'warning' : ''].join(' ')}><i />{status.replace('_', ' ')}</span>;
}

function PaymentRows({ items }: { items: PaymentIntent[] }) {
  return <div className="payment-list">{items.map((item) => <div className="payment-row" key={item.id}><span className="payment-icon">{item.operation === 'deposit' ? <ArrowDownLeft size={17} /> : <ArrowUpRight size={17} />}</span><div><strong>{item.operation === 'deposit' ? 'Sandbox deposit' : 'Sandbox withdrawal'}</strong><small>{item.intent_reference} · {item.amount_minor.toLocaleString()} {item.currency_code}</small></div><Status status={item.status} /></div>)}</div>;
}

export function PaymentsPage({ mode = 'overview' }: { mode?: 'overview' | 'deposit' | 'withdrawal' }) {
  const payments = usePayments();
  const methods = usePaymentMethods();
  const deposit = useCreateDeposit();
  const withdrawal = useCreateWithdrawal();
  const stepUp = useStepUp();
  const form = useForm<FormValues>({ resolver: zodResolver(schema) as any, defaultValues: { payment_method: 'card', provider_scenario: 'processing' } });
  const submit = (values: FormValues) => {
    if (mode === 'withdrawal') {
      stepUp.mutate(undefined, { onSuccess: (challenge) => withdrawal.mutate({ ...values, challenge_id: challenge.data.challenge_id, challenge_code: challenge.data.sandbox_code }) });
      return;
    }
    deposit.mutate(values);
  };
  const mutation = mode === 'withdrawal' ? withdrawal : deposit;
  const heading = mode === 'deposit' ? 'Add money' : mode === 'withdrawal' ? 'Withdraw money' : 'Payment activity';
  return <main className="page"><div className="page-heading"><div><span className="eyebrow">SANDBOX PAYMENTS</span><h1>{heading}</h1><p className="subtle">Provider orchestration is connected to demo adapters only. No live funds are moved.</p></div><span className="demo-badge"><Sparkles size={13} /> Sandbox only</span></div><div className="demo-notice"><LockKeyhole size={17} /><span><strong>Financial safety boundary.</strong> Provider callbacks are verified before any sandbox ledger settlement. Live cards, bank accounts, payouts, and refunds are disabled.</span></div>{mode !== 'overview' && <section className="transfer-grid"><section className="side-card"><div className="side-card-head"><div><h3>{mode === 'deposit' ? 'Create a deposit intent' : 'Create a withdrawal intent'}</h3><p>Amounts are integer minor units and routed through the sandbox mock provider.</p></div><CreditCard size={20} /></div><form className="compact-form" onSubmit={form.handleSubmit(submit)}><label>Wallet currency account ID<input {...form.register('wallet_currency_account_id')} placeholder="UUID" />{form.formState.errors.wallet_currency_account_id && <small className="form-error">{form.formState.errors.wallet_currency_account_id.message}</small>}</label><label>Amount in minor units<input type="number" {...form.register('amount_minor')} placeholder="10000" /></label><label>Payment method<select {...form.register('payment_method')}>{(methods.data?.data ?? [{ code: 'card', sandbox_only: true }, { code: 'bank_transfer', sandbox_only: true }]).map((method) => <option key={method.code} value={method.code}>{method.code} · sandbox</option>)}</select></label><label>Provider scenario<select {...form.register('provider_scenario')}><option value="processing">Processing — await verified webhook</option><option value="failed">Failed</option><option value="unknown">Unknown — reconciliation required</option></select></label>{mode === 'withdrawal' && <div className="dialog-note"><AlertTriangle size={15} /> A sandbox step-up challenge is issued before the withdrawal intent. No live payout is possible.</div>}<button className="primary-btn full" disabled={mutation.isPending || stepUp.isPending}>{mutation.isPending || stepUp.isPending ? 'Creating sandbox intent…' : 'Create ' + mode + ' intent'} <ArrowUpRight size={16} /></button></form>{mutation.isError && <div className="wallet-error"><AlertTriangle size={16} />{(mutation.error as Error).message}</div>}{stepUp.isError && <div className="wallet-error"><AlertTriangle size={16} />Sandbox step-up could not be issued.</div>}{mutation.data && <div className="payment-result"><strong>Intent created</strong><Status status={mutation.data.data.status} /><small>{mutation.data.data.intent_reference} · awaiting verified provider event</small></div>}</section><section className="side-card"><div className="side-card-head"><div><h3>Payment lifecycle</h3><p>Each status transition is server-controlled and auditable.</p></div><Clock3 size={20} /></div><div className="payment-timeline"><span><i />Intent created</span><span><i />Provider attempt recorded</span><span><i />Verified webhook required</span><span><i />Ledger settlement or reconciliation</span></div></section></section>}{mode === 'overview' && <section className="side-card"><div className="side-card-head"><div><h3>Payment directory</h3><p>Deposit and withdrawal intents with sandbox status.</p></div><CreditCard size={20} /></div>{payments.isLoading ? <div className="empty-state compact"><p>Loading payment activity…</p></div> : payments.isError ? <div className="wallet-error"><AlertTriangle size={16} />Unable to load payment activity.</div> : <PaymentRows items={payments.data?.data ?? []} />}</section>}</main>;
}
