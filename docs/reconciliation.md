# Reconciliation

`ReconciliationService` runs read-only integrity checks and writes a `reconciliation_runs` record with `reconciliation_items` exceptions.

Checks include:

- debit/credit equality for every posted journal and currency;
- ledger-derived wallet projection versus `balance_projections`;
- active hold totals versus projected held amounts;
- missing wallet ledger accounts.

Differences are reported as exceptions. The service never edits projections, creates corrective postings, or silently changes balances. Corrective action must be an authorized, separately audited financial workflow.

Run the sandbox reconciliation command with:

```bash
php artisan ledger:reconcile
```

System chart-of-accounts records can be provisioned without creating balances:

```bash
php artisan ledger:seed-accounts
```

Production operations still need scheduled execution, alerting, retention, independent review, and PostgreSQL transaction/concurrency testing.
# Phase 6 transfer reconciliation

Transfer reconciliation must compare every transfer's hold state, transfer event state, provider attempt state, and linked ledger transaction. A completed transfer is valid only when the source hold is captured exactly once and the linked journal is posted and balanced. Failed, timed-out, cancelled, and returned transfers must have released holds and released limit usage. Differences are exceptions for review; the system must never silently edit a balance or mark an external provider as settled.
