# Safeguarding readiness

Safeguarding reconciliation is currency-specific and uses posted ledger-derived expected amounts plus verified external evidence. Amounts are stored as integer minor units. USD, EUR, GBP, SGD, THB, and MMK are never added together without an explicit valuation process.

Missing external evidence produces `pending_evidence`, not a zero balance. Variances create exceptions for investigation and escalation. The dashboard is read-only and has no balance-adjustment operation.
