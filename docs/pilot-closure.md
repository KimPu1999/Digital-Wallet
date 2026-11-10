# Pilot Closure

Closure requests require an idempotency key and two-person approval. The request records closure status and changes the pilot to `closure_requested`; the API does not delete financial or audit records and does not perform an automatic production or ledger action.

A complete closure review should confirm final currency-specific reconciliation, outstanding exceptions, incidents, support tickets, evidence archival and approved evaluation before any future closure executor is introduced.
