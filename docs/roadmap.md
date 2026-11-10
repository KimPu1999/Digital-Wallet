# Roadmap

1. Phase 1: product foundation, layouts, authentication UI, metadata migrations, API boundary, and demo dashboard.
2. Phase 2: onboarding, email verification, real role provisioning, KYC workflow, and operational admin review.
3. Phase 3: multi-currency wallet metadata, explicit customer/merchant ownership, preferences, status history, dashboard management, merchant wallet views, and read-only admin monitoring.
4. Phase 4: double-entry ledger implementation, idempotency, holds, reversals, reconciliation, immutable postings, and read-only financial monitoring. No payment rails are enabled.
5. Phase 5: sandbox FX quotes, fee/spread calculation, provider abstraction, two-currency ledger conversion posting, idempotency, and FX monitoring. No live provider or settlement is enabled.
6. Phase 6: sandbox beneficiary management, transfer quotes, internal wallet transfers, limits, step-up challenges, mock screening/provider adapters, hold settlement, timelines, receipts, and admin monitoring.
7. Phase 7: sandbox payment provider ports, payment intents, signed webhooks, deposit/payout state machines, payout holds, provider transactions, settlement matching, and reconciliation exceptions. Production money movement remains disabled.
8. Phase 8: merchant payment links, hosted checkout boundary, QR references, sandbox wallet checkout, refund accounting, projected settlements, merchant API keys, webhook endpoint security, and merchant analytics. No live payments are enabled.
9. Phase 9: platform admin roles and permissions, compliance/risk operations, maker-checker approvals, append-only audit surfaces, settings history, health monitoring, and currency-separated financial operations. No live payments are enabled.
10. Phase 10: PostgreSQL concurrency certification, approved provider integrations, hosted/tokenized payment collection, production compliance controls, operational dual approval, fraud/chargeback operations, and regulatory/production authorization review.
## Phase 10 delivered

- Versioned sandbox risk rules and explainable alerts.
- Screening provider abstraction with safe mock failure handling.
- Fraud signals for authentication and device risk.
- Customer-scoped security overview, devices, sessions, and auth events.
- Compliance case lifecycle, restricted evidence metadata, and report drafts.
- Platform-protected security, AML, screening, fraud, and case monitoring routes.

Phase 11 should add production-provider governance, jurisdiction-specific policy approval, independently reviewed alert workflows, and PostgreSQL integration/concurrency coverage before any live capability.

## Phase 11 delivered

- Deduplicated in-app notification events and preference records.
- Mock-safe email, SMS, push, and in-app channel adapters.
- Customer and merchant support tickets with internal-note privacy.
- Agent assignment, SLA due times, status transitions, and secure attachment intake.
- Separate dispute case lifecycle and platform communication dashboards.
## Phase 12 completed foundation

Read-only multi-currency financial reporting, merchant analytics, report runs, private CSV export metadata, and period/snapshot foundations are implemented. Remaining production work includes PostgreSQL performance validation, queued large exports, XLSX/PDF libraries, scheduled reports, approved valuation sources, and period-close governance.

## Phase 13 infrastructure foundation

Container, CI/CD, Terraform contract, observability, backup, disaster-recovery, and deployment-safety foundations are documented and templated. Provider-specific infrastructure, restore evidence, actual telemetry, and production approvals remain outstanding.

# Phase 14 — QA and release assurance

- Maintain an evidence-backed QA test-run, finding, performance and recovery model.
- Keep release gates blocked when required evidence is absent.
- Complete PostgreSQL concurrency, browser accessibility, load, dependency, container and restore validation in isolated CI/staging environments before any production approval.

# Phase 16 — Mobile QA and sandbox beta

- Complete Flutter tooling validation and generate native Android/iOS projects.
- Implement and test transfer, FX, QR, notifications, support, KYC and security journeys.
- Import CI builds, mobile test runs, crash reports and beta feedback into the mobile operations dashboard.
- Keep Android/iOS release gates blocked until actual device, security, accessibility and beta approval evidence exists.

# Phase 17 — Enterprise maturity

- Complete PostgreSQL-backed governance, privacy, lifecycle and incident integration tests.
- Populate enterprise operations from authorized production-like telemetry only.
- Complete policy ownership, access reviews, release approvals and retention review.
- Keep financial feature activation and production deployment behind independent approvals.

# Phase 18 — Regulatory readiness and controlled launch

- Complete qualified legal review for every target jurisdiction and regulated activity.
- Replace placeholder evidence references with independently verified private evidence.
- Complete partner due diligence, agreements, production capability approvals, and safeguarding source validation.
- Complete consumer-protection, privacy, security-assurance, operational-resilience, and incident-readiness reviews.
- Run PostgreSQL reconciliation and authorization tests for the new readiness domain.
- Keep all launch gates blocked until external approvals exist; no dashboard action enables live money movement.

# Phase 19 — Controlled sandbox pilot operations

- Build sandbox-only pilot programs, cohorts and participant enrollment.
- Enforce account, KYC/KYB, risk, schedule, jurisdiction, currency and feature eligibility server-side.
- Apply currency-aware pilot limits without inventing regulatory thresholds.
- Monitor sandbox activity, provider telemetry, queues, webhooks, reconciliation and exceptions.
- Require independent approvals for evaluation decisions and auditable pilot closure.
- Keep live financial execution disabled; a pilot decision is not legal, partner or production authorization.

# Phase 20 — Final audit and long-term maintenance

- Keep the quality center evidence-backed for module coverage, financial integrity, infrastructure and release gates.
- Complete PostgreSQL-specific financial, authorization, backup/restore and concurrency validation.
- Populate maintenance ownership, due dates and evidence references.
- Complete Flutter, browser accessibility, dependency, container and controlled performance checks where tooling is available.
- Keep software release readiness separate from regulatory authorization and live financial activation.
