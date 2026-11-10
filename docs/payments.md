# Payment orchestration

Phase 7 adds a sandbox-only payment intent boundary. Customers can create deposit and withdrawal intents through /api/v1/payments/*; they cannot post journals, edit balances, select arbitrary providers, or mark a payment successful.

The lifecycle is:

created -> processing -> succeeded, failed, returned, or unknown

The orchestrator validates wallet ownership, email verification, country capability, KYC where configured, currency, payment method, limits/holds, and idempotency before routing to an eligible provider. A provider response creates an attempt but does not settle money. A deposit is credited only after a verified, deduplicated provider webhook and a successful balanced ledger journal. A withdrawal reserves a wallet hold before provider submission and captures it only after verified success; failures release the hold.

unknown is a deliberate operational state for uncertain provider outcomes. It creates a reconciliation exception and is not blindly retried or failed over.

The sandbox mock provider is the only enabled provider. Stripe, Wise Platform, and Airwallex adapters are explicit capability stubs and return unsupported/not-configured responses until separately reviewed and configured. No raw card number, CVV, bank credential, or provider secret is stored.

## Accounting treatment

- Pending funding: payment intent and provider attempt only; no wallet ledger credit.
- Settled funding: debit restricted provider clearing and credit the wallet liability in the same currency.
- Payout pending: active wallet hold; no posted debit.
- Settled payout: capture the hold with a debit to the wallet liability and credit to a restricted provider payout account.
- Fees, refunds, and chargebacks: reserved for future explicit commands and accounts; no arbitrary customer endpoint exists.

## API

Customer routes include GET /payment-methods, deposit/withdrawal intent creation, GET /payments, payment detail/timeline, and the signed webhook endpoint. Admin routes are read-only monitoring for payments, providers, webhooks, settlement batches, and reconciliation exceptions.
