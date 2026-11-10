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
