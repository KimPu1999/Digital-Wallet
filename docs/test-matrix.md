# Phase 1–13 Test Matrix

| Area | Repository evidence | Status | Phase 14 verification |
|---|---|---|---|
| Foundation/authentication | Laravel feature tests and Sanctum routes | Partially covered | Existing suite passed; browser/MFA expansion remains |
| Multi-currency wallets | Wallet migrations, ownership services and wallet tests | Implemented in sandbox | Regression tests and PostgreSQL concurrency required |
| Ledger/holds/idempotency | Ledger engine tests and immutable schema | Implemented in sandbox | PostgreSQL locking suite required |
| FX/transfers | FX and transfer feature tests | Implemented in sandbox | Provider and concurrency evidence required |
| Payments/merchant checkout | Payment, provider and checkout tests | Implemented in sandbox | External-provider behavior remains unverified |
| Platform administration | Platform admin/security tests and policies | Implemented in sandbox | Browser and full privilege matrix required |
| AML/fraud/compliance | Security/compliance feature tests and domain services | Implemented in sandbox | Screening-provider and case access integration required |
| Notifications/support | Communication/support tests and private ownership checks | Implemented in sandbox | Queue/broadcast/attachment integration required |
| Reporting | Financial reporting tests and read-only services | Implemented in sandbox | PostgreSQL scale/performance evidence required |
| Infrastructure/operations | Docker, CI, Terraform contracts, health, backup runbook | Partially implemented | Docker/cloud/restore execution unavailable locally |
| QA/release evidence | QA tables, service, API and Control Center | Implemented | Evidence population and release review pending |

“Implemented” means code exists in the repository; it does not mean production readiness or regulatory approval.

