# Financial operations

Financial operations use the Phase 4 immutable ledger, Phase 5 FX records, Phase 6 transfers, Phase 7 payment intents, and Phase 8 merchant payment records. Platform APIs are read-only monitoring surfaces. Totals are grouped by currency and use BIGINT minor units.

Reconciliation exceptions are reported and never silently auto-corrected. Corrective activity must use an authorized, audited compensating posting through the internal ledger service. No admin endpoint accepts arbitrary journal lines or a mutable balance.
