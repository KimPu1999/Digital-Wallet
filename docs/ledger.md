# Ledger engine

Phase 4 adds an append-only, double-entry ledger. Wallet currency accounts remain account metadata; their `ledger_accounts` record is a liability account with a credit normal side. Platform settlement accounts are assets with a debit normal side. Fee accounts are revenue with a credit normal side. Restricted suspense accounts are never used by customer-facing commands.

## Posting convention

Every posted journal contains at least two positive minor-unit entries. Entries are grouped by `currency_code`; for every currency, total debits must equal total credits. There is no implicit FX conversion. A future FX workflow must post separate balanced currency legs through explicit bridge or clearing accounts.

Amounts are BIGINT minor units. Floating point values are not accepted by the posting service. Currency exponent metadata belongs to `currencies` and is presentation/accounting configuration, not an exchange-rate engine.

`LedgerPostingService` validates the command, locks all referenced accounts in deterministic ID order, checks active status and currency, checks wallet liability availability, creates the pending journal and immutable entries, marks it posted, updates projections, and emits financial audit/outbox records in one database transaction. PostgreSQL also installs a deferred constraint trigger that rejects a posted journal whose currency legs are not balanced.

There are no public posting endpoints. Financial commands are internal service calls and are still sandbox-only.

## Balances

The ledger is authoritative. `balance_projections` is a rebuildable read model containing posted and held minor units plus a version. For a wallet liability:

```text
available = posted - held
```

Projection changes are made in the same transaction as the journal or hold transition. Rebuilds compare ledger-derived values and do not silently correct differences.

## Reversals

Posted entries are never edited or deleted. A reversal creates a new compensating journal, links it through `ledger_reversals`, records an actor and reason, and rejects duplicate reversals of the same original transaction.

## Idempotency

Financial commands use a scoped idempotency key and SHA-256 request fingerprint. The unique `(scope, key)` constraint and row lock protect reservation/completion. A completed request replays its original transaction reference; a reused key with different payload is rejected.
## Transfer settlement integration

Sandbox internal transfers use the existing wallet-hold capture path and immutable ledger posting service. No transfer service writes a balance directly. A future external settlement callback must be authenticated, idempotent, state-validated, and reconciled against the transfer hold and ledger transaction before completion.

## Merchant checkout and refunds

Wallet checkout debits the customer wallet liability and credits the merchant wallet liability in the same currency. The payment intent and checkout session are updated only after the balanced journal posts. A refund credits the customer liability and debits the merchant liability through a new compensating journal linked to the refund request; the original posted entries remain immutable. Merchant settlement reports are derived summaries and are not ledger postings without matching settlement evidence.

## Platform operations

Platform financial views read immutable ledger, FX, transfer, payment, refund, and settlement records. Aggregation is grouped by currency and date range. Admin approvals and audit references can authorize a future compensating command, but they never mutate a posted journal or bypass ledger validation.
