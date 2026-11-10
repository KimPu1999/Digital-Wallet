# Financial Integrity Verification

The ledger remains the financial source of truth. Balances are derived from immutable posted entries; holds and projections are not authoritative money creation. Amounts use integer minor units and entries balance per currency.

## Required checks

- Every finalized journal balances debits and credits by currency.
- Posted entries cannot be edited or deleted through application APIs.
- Idempotency keys prevent duplicate financial commands.
- Deterministic locks protect concurrent spending and hold transitions.
- Reversals are compensating journals, never edits.
- Reports exclude pending entries and do not combine currencies without valuation metadata.

## Evidence status

The repository’s existing ledger feature tests passed in the local SQLite harness. PostgreSQL locking, deadlock, isolation and restore validation were not executed because PostgreSQL was not running locally. SQLite evidence is not a substitute for those checks.

## Phase 20 quality-center rule

The quality center reads only posted ledger transactions and groups debit/credit totals by currency. Any currency imbalance or reconciliation exception blocks the financial-integrity gate.
