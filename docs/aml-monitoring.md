# AML monitoring (Phase 10)

Phase 10 adds a sandbox-only, explainable monitoring layer around existing payment, transfer, and FX records. The risk_rules and risk_rule_versions tables hold versioned configuration; risk_evaluations records the decision and evidence; risk_alerts is the analyst queue.

Rules are deliberately generic. No jurisdiction-specific threshold is presented as a regulatory rule. A missing or failed required screening result produces review, never allow. Monitoring does not post to, edit, or reverse ledger entries.

Platform access requires an authenticated platform membership, active account, verified MFA, and the corresponding Phase 9 permission. Production requires jurisdictional policy review, provider governance, retention rules, and independent validation.
