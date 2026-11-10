# Phase 6 transfer engine

Phase 6 is sandbox-only. It does not connect to banks, licensed payout providers, correspondent networks, or live remittance rails.

## State machine

draft -> quoted -> awaiting_confirmation -> pending_compliance -> processing -> completed

pending_compliance -> rejected or failed
processing -> cancelled or returned
quoted/awaiting_confirmation -> expired or cancelled

Transitions are enforced by TransferStateMachine; controllers cannot assign arbitrary statuses.

## Beneficiaries

Beneficiaries are explicitly owned by a customer or merchant context. Account details are encrypted with Laravel application encryption. API resources return only account type, currency, status, and a last-four mask. Full details are never written to logs or returned to clients.

Supported account types are internal_wallet, bank_account, and payout_provider. Only internal wallet settlement is financially posted in this phase; external types use the sandbox settlement account and provider simulator.

## Quotes

TransferQuoteService validates the source wallet, beneficiary ownership, country transfer capability, KYC requirement, amount, currency corridor, and server-side fee/rate calculation. Quotes expire by timestamp and are immutable financial instructions. The frontend never supplies a trusted recipient total.

## Execution

Execution requires an authenticated user, an unexpired quote, a one-time sandbox step-up challenge, an idempotency key, a limit reservation, mock screening, and deterministic wallet-hold operations. Accepted sandbox provider results capture the hold through the Phase 4 ledger. Failed, timed-out, or returned results release the hold and the reserved limit.

Same-currency internal transfers debit the sender wallet liability and credit the recipient wallet liability atomically. International/external transfers debit the source wallet and credit a sandbox settlement account; the recipient amount is informational until a future licensed settlement integration exists.

## Limits and reconciliation

Transfer limits are database-backed by currency and effective date. Daily and monthly usage rows are locked before increments to prevent concurrent bypasses. The transfer reconciliation workflow must compare holds, transfer attempts, ledger postings, and provider callbacks.

## Production restrictions

The sandbox step-up response includes a development-only challenge code. This must be replaced by an independently reviewed MFA/WebAuthn flow before production. The mock screening adapter is not compliance clearance, and mock provider completion is not settlement confirmation.
