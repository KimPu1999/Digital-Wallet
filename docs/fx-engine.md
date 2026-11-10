# Phase 5 FX engine

Phase 5 adds a sandbox-only foreign-exchange quote and conversion boundary. It does not connect to a rate vendor, bank, payment rail, or settlement account.

## Rate and quote flow

1. A user requests a pair, source wallet currency account, target wallet currency account, and positive integer amount in minor units.
2. FxQuoteService checks wallet policy, country capability, pair availability, amount limits, and the configured sandbox provider.
3. The provider returns a decimal-string rate. A snapshot records the provider reference, observation time, expiry, and metadata.
4. Fixed and percentage fees are calculated with BCMath. Provider spread and platform markup reduce the executable rate. Target amounts are rounded down to the target currency's minor unit.
5. The issued quote is immutable and expires by timestamp. A quote is consumed at most once by the unique conversion relation.

Quotes store both the mid-market and executable rates. They are not promises of a live market price.

## Provider boundary

FxRateProviderInterface is the only provider contract. MockFxRateProvider is deterministic, explicitly sandbox typed, and has no network fallback. A future adapter must define timeout, stale-rate, authentication, health, and circuit-breaker behavior before it is enabled.

## Ledger integration

Conversion posting uses two balanced currency legs rather than cross-currency netting:

- source currency: debit the source wallet liability, credit source FX clearing, and credit fee revenue when a fee exists;
- target currency: debit target FX clearing and credit the target wallet liability.

LedgerPostingService validates each currency independently, locks accounts deterministically, checks source available funds, and commits the journal atomically. No FX conversion endpoint accepts arbitrary journal lines.

## Idempotency and safety

Conversion requests require Idempotency-Key, are scoped to the authenticated user, fingerprint the quote payload, and replay the completed conversion. Reusing a key with different quote data is rejected. The quote unique constraint and ledger posting boundary prevent duplicate consumption.

## Operational limitations

- Rates are deterministic demo rates, not market rates.
- No live FX provider or external settlement is enabled.
- No customer deposit, withdrawal, transfer, or cash-out capability is enabled.
- PostgreSQL is required for production concurrency and deferred ledger constraints; the local SQLite suite does not prove PostgreSQL locking behavior.
- FX reconciliation records are read-only exception records. Differences must be investigated and corrected through an authorized future workflow, never by editing balances.

Run php artisan fx:reconcile to compare completed conversion source/target legs with their immutable ledger transaction. A non-zero exception result is an operational alert; it does not auto-correct any record.
