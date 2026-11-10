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
## Phase 7 controls

Payment operations require authenticated ownership, email verification, country eligibility, configured KYC gates, server-side provider routing, scoped idempotency keys, and rate limiting. Withdrawals additionally require step-up MFA and an atomic wallet hold. Provider webhooks verify signatures and replay windows before event deduplication and settlement. Customer, merchant, and platform-admin reads remain isolated by policy and role middleware.

Provider credentials are encrypted/server-side only. Raw PAN/CVV and bank credentials are not accepted. Production requires hosted/tokenized collection, MFA enforcement, fraud/AML monitoring, provider allowlists, secrets rotation, restricted database roles, backup/restore tests, incident response, and regulatory approval appropriate to the operating model.

## Phase 8 controls

Merchant links, checkout sessions, and QR references use opaque bearer tokens whose hashes are stored at rest. Wallet checkout requires customer ownership, email verification, step-up MFA, scoped idempotency, deterministic ledger locks, and a successful balanced journal. Refunds require merchant authorization and compensate the original ledger transaction. Merchant API keys are SHA-256 hashed, scoped, expirable, revocable, and shown once. Webhook endpoints require public HTTPS and reject private/internal destinations to reduce SSRF risk; signing secrets are encrypted. These are sandbox controls and still require independent review before production.
