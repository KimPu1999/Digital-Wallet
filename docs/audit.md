# Platform audit

`platform_audit_events` records actor, action, resource, correlation ID, decision, approval reference, and non-sensitive previous/new state. `admin_security_events` records security-relevant events without secrets. Financial audit events remain in the Phase 4 ledger domain. Identity documents, passwords, MFA secrets, provider credentials, API keys, and webhook signing secrets are never written to these logs.

Production deployment must restrict database write access to the audit writer, add append-only database protections, retention controls, tamper-evident export, monitoring, and independent access review.
