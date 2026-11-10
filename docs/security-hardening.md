# Security Hardening Audit

The platform uses Sanctum authentication, server-side platform permissions, ownership policies, merchant scoping, MFA/step-up challenges, rate-limited sensitive routes, idempotency keys, webhook verification, private evidence references and append-only audit records.

No production secrets, raw card data, CVV, passwords, identity documents or arbitrary ledger-posting API are exposed by the Phase 20 quality center. A complete dynamic IDOR, dependency, container, browser and PostgreSQL security assessment remains an environment-dependent task.
