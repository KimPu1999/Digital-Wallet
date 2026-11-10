# Security

Sanctum SPA authentication should use the CSRF cookie plus secure, HTTP-only session cookies. The frontend does not store tokens in localStorage. API authorization must remain server-side with policies and merchant membership checks; hiding a route in React is not an authorization control.

Before production, add MFA, KYC/AML workflows, fraud monitoring, encryption and key rotation, backups and restore drills, structured audit retention, CORS allowlists per environment, security headers, dependency scanning, rate limits by identity and IP, and a regulatory/legal review. Do not make claims about licensing, insurance, deposit protection, or bank partnerships without evidence.

Phase 2 adds RFC 6238-compatible TOTP enrollment, encrypted MFA secrets, hashed one-time recovery codes, login activity, account status, generic credential errors, private KYC storage, and country capability gates. The TOTP implementation must be independently reviewed against a maintained authentication package before production. KYC documents remain pending scan/review; upload is never approval.
