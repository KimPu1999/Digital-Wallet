# Pilot Operations Architecture

Phase 19 adds a sandbox pilot operations boundary around existing identity, KYC/KYB, risk, ledger, reconciliation, incident and approval domains.

`pilot_programs` define the immutable environment boundary: `environment=sandbox` and `sandbox_only=true`. Scope versions, cohorts, participant records, eligibility checks, limits, activity events, monitoring snapshots, reconciliation reviews, exceptions, evaluations and decisions are stored separately from posted financial records.

The pilot domain is observational and governance-oriented. It does not post ledger entries, activate production providers, or create an administrator shortcut for live money movement. Any future financial command must independently pass existing authorization, compliance, risk, idempotency and ledger posting boundaries.
