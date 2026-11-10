# Compliance case management (Phase 10)

Compliance cases are separate from the existing Phase 9 general risk_cases table. Cases have audited state transitions: open, assigned, investigating, awaiting_information, escalated, resolved, and closed. Material transitions require a reason.

Evidence stores a restricted reference and metadata only. Raw identity documents and storage secrets are not returned by the monitoring API. Case access is protected by platform compliance permissions and all decisions are logged through the append-only platform audit stream.
