# Security

Sanctum SPA authentication should use the CSRF cookie plus secure, HTTP-only session cookies. The frontend does not store tokens in localStorage. API authorization must remain server-side with policies and merchant membership checks; hiding a route in React is not an authorization control.

Before production, add MFA, KYC/AML workflows, fraud monitoring, encryption and key rotation, backups and restore drills, structured audit retention, CORS allowlists per environment, security headers, dependency scanning, rate limits by identity and IP, and a regulatory/legal review. Do not make claims about licensing, insurance, deposit protection, or bank partnerships without evidence.

Phase 2 adds RFC 6238-compatible TOTP enrollment, encrypted MFA secrets, hashed one-time recovery codes, login activity, account status, generic credential errors, private KYC storage, and country capability gates. The TOTP implementation must be independently reviewed against a maintained authentication package before production. KYC documents remain pending scan/review; upload is never approval.

Phase 3 adds server-derived wallet ownership, merchant membership scoping, email-verification and country/KYC checks for wallet creation, duplicate-account protection, transaction boundaries, status-history audit events, read-only admin monitoring, and no balance mutation endpoint. Admin directory access is role middleware protected; frontend route visibility is not treated as authorization.

Before production, complete MFA enforcement for privileged actions, KYC/AML policy review, fraud and sanctions monitoring, encryption and key management, backup/restore testing, security headers, dependency scanning, abuse/rate-limit tuning, privacy review, incident response, and regulatory/legal approval. Do not enable money movement until the Phase 4 ledger and operational controls are independently reviewed.

Phase 4 adds deterministic ledger-account locks, projection locks for holds, scoped idempotency fingerprints, immutable financial records, authorization-aware wallet balance/activity reads, and read-only admin ledger APIs. PostgreSQL must be the test database for concurrency, transaction isolation, deferred balance triggers, and immutable-record enforcement before production use. The current local environment has no reachable PostgreSQL service.
## Phase 5 controls

FX quote and conversion routes require Sanctum authentication, wallet policy ownership/access, country FX capability, server-side validation, per-user rate limits, scoped idempotency, and audit events. Quotes and conversions are marked sandbox_only. The only registered provider is a deterministic mock provider; provider errors do not silently fall back to another rate.

Phase 6 adds beneficiary ownership checks, encrypted account details, transfer quote expiry, database-backed limit locks, one-time step-up challenge consumption, mock screening records, provider-attempt records, and hold/ledger atomicity. The development-only step-up code and mock screening result must be replaced before production.

Production work must add provider credential isolation, stale-rate policy, circuit breakers, market-data monitoring, rate manipulation controls, approval workflows, and PostgreSQL concurrency tests before enabling any external provider.
