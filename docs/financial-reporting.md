# Financial reporting

Phase 12 reporting is read-only. Posted entries in `ledger_entries`, joined to posted `ledger_transactions`, are the financial source of truth. Pending, failed, and unposted journal records are excluded from statements.

Reports cover overview, trial balance, general ledger, profit and loss, balance sheet, wallet, hold, transfer, FX, payment, refund, settlement, reconciliation, and merchant-scoped analytics. Trial balance and statements keep each currency separate. No currencies are added together without an explicit valuation record.

All amounts are integer minor units. Future base-currency views must use `report_valuations` with source, timestamp, version, valuation date, and rounding policy. Missing rates must produce an unavailable valuation, not a fabricated total.

Report ranges are bounded to 366 days. Definitions are versioned, runs record parameters and checksums, and snapshots record source watermarks and valuation references. This is sandbox reporting and is not an audited financial statement.
