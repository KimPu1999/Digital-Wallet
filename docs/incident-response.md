# Incident response

Severity and ownership are explicit: API/database outages are platform operations; queue/webhook failures are operations; ledger or reconciliation exceptions are finance operations; suspicious admin access and secrets are security; compliance data incidents include compliance/legal escalation.

Every incident records start time, correlation IDs, affected environment, feature-flag state, operator, evidence, decisions, communication status, and post-incident actions. Financial incidents require immutable audit references and independent review. Never disclose secrets or identity documents in incident tickets.

Runbook links are referenced by Prometheus alert annotations. Alert thresholds are starting points and must be tuned against measured staging telemetry.
