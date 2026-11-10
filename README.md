# Digital Wallet Platform

Phase 9 foundation for a professional digital wallet product. This repository is deliberately sandbox-only: ledger balances, FX conversions, transfers, deposits, withdrawals, provider callbacks, merchant checkout, payment links, QR references, refunds, settlement records, and platform operations are demo/test state. Real deposits, payouts, banking rails, card collection, merchant acceptance, and production payment processing are not enabled.

## Requirements

- Node.js 20+ and npm
- PHP 8.2+
- Composer
- PostgreSQL 15+
- PHP BCMath extension (required for decimal-safe FX quote calculations)

## Setup

### Backend

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

Set the PostgreSQL values in `backend/.env` before migrating. Do not use destructive reset commands against an existing database. The Phase 3 migration adds wallet ownership, currency accounts, preferences, and status history without adding a mutable balance source of truth.

### Frontend

```bash
cd frontend
cp .env.example .env
npm install
npm run dev
```

Open `http://localhost:5173`. The public site, light/dark theme, EN/MM switch control, customer shell, demo dashboard, and auth UI are available without an API connection. The backend API URL is configured through `VITE_API_URL`; never put backend secrets in Vite variables.

## Verification

```bash
cd frontend && npm run typecheck && npm run build
cd backend && php artisan test
```

The verification suite uses an isolated in-memory SQLite database. PostgreSQL remains the supported production-like datastore and must be provisioned separately before claiming a PostgreSQL connection check.

## Demo data and wallet setup

```bash
cd backend
php artisan db:seed
php artisan admin:provision admin@example.com "Platform Admin" --role=platform_super_admin
```

The seed catalog contains USD, EUR, GBP, SGD, THB, and MMK. Currency availability is configuration only; it does not approve a country, payment corridor, or live financial activity. Customer wallet creation requires verified email, country capability, and (where configured) approved KYC. Additional currencies are added through the wallet currency-account endpoint.

The seed also enables only sandbox-mock. Stripe, Wise Platform, and Airwallex are disabled provider adapter stubs. Set PAYMENT_SANDBOX_WEBHOOK_SECRET only in the backend environment when testing signed mock callbacks; never use a production provider secret in this sandbox.

## Ledger sandbox

```bash
cd backend
php artisan ledger:seed-accounts
php artisan ledger:reconcile
php artisan fx:reconcile
```

Ledger posting is internal-only. The customer and merchant APIs expose read-only balances and ledger activity; there are no public arbitrary journal, deposit, withdrawal, transfer, hold, capture, or reversal commands.

## FX sandbox

The seed creates deterministic sandbox pairs and fee rules. FX requires the authenticated user's country capability fx_enabled; this is a sandbox feature flag, not a regulatory, corridor, or provider approval. Quote creation and conversion use /api/v1/fx/*, require wallet access, and mark every response sandbox_only. Conversion execution requires an Idempotency-Key.

## Transfer sandbox

Transfer beneficiaries, quotes, step-up challenges, limits, screening records, holds, and timelines are available under /api/v1/beneficiaries and /api/v1/transfers. The customer UI is available at /app/transfers and /app/beneficiaries; platform monitoring is available at /api/v1/admin/transfers. Seeded limits are demo configuration only. No live payout provider is configured.

## Payment sandbox

Payment intents and provider monitoring use /api/v1/payments, /api/v1/webhooks/payments/{provider}, and the read-only /api/v1/admin payment routes. Deposits require a verified mock webhook before sandbox ledger settlement. Withdrawals require step-up MFA and reserve a wallet hold; only a verified provider success can capture it. Unknown provider outcomes remain pending reconciliation and are never blindly retried.

## Merchant checkout sandbox

Merchant operations use `/api/v1/merchants/{merchant}` for payment links, checkout sessions, QR references, refunds, projected settlements, analytics, test API keys, and webhook endpoint registration. Customer wallet checkout is the only executable merchant payment path. Hosted card collection is not enabled because provider adapters remain explicit test-mode/unsupported boundaries. Payment link and checkout tokens are opaque bearer values; QR generation is not proof of payment; refunds post compensating ledger entries; settlements remain `projected` without provider evidence. The merchant console is available at `/merchant` and related routes.

## Platform admin sandbox

Platform operations use `/api/v1/admin/platform/*` and the `/admin` frontend routes. Access requires an active platform-admin membership, explicit permission, and verified MFA. Sensitive restrictions, compliance decisions, settings changes, and approval decisions require a one-time step-up challenge; maker-checker requests prevent self-approval. Financial monitoring is read-only and currency-separated. Provision staff only through the controlled command:

```bash
cd backend
php artisan admin:provision admin@example.com "Platform Admin" --role=platform_super_admin
```

## Phase 9 readiness and limitations

- Provision a clean PostgreSQL database and run migrations.
- Install Laravel/Sanctum dependencies and configure stateful domains and CORS.
- Configure Sanctum stateful domains, secure cookies, CORS, and PostgreSQL.
- Add real test fixtures using a separate test database.
- Review merchant membership fixtures and admin policy coverage.
- Run financial concurrency tests on PostgreSQL, not SQLite.
- Configure an approved hosted collection/tokenization flow; never accept raw card data.
- Complete provider account approval, compliance review, dual control, fraud controls, and operational runbooks.
- Do not enable production environment providers until legal, regulatory, safeguarding, reconciliation, incident response, and security reviews are complete.
- Run merchant checkout, refund, webhook, and PostgreSQL concurrency tests against a separate PostgreSQL test database.
- Add a reviewed API-key authentication gateway, signed merchant webhook worker, provider-hosted checkout, chargeback handling, and settlement evidence before any live merchant acceptance.
- Run platform-admin PostgreSQL authorization, approval, audit immutability, and aggregation tests against a separate PostgreSQL database.
- Replace sandbox step-up codes with production MFA verification and enforce dual control through operational identity governance.

## Commands

```bash
cd backend
php artisan test
```

Phase 2 adds country eligibility, separate merchant/admin authentication endpoints, email/password recovery hooks, MFA enrollment, private KYC document intake, and compliance schema. Phase 3 adds multi-currency wallet metadata. Phase 4 adds the immutable ledger and holds. Phase 5 adds sandbox FX. Phase 6 adds sandbox transfers. Phase 7 adds provider ports, payment intents, signed mock webhooks, payout holds, settlement records, and reconciliation monitoring. Phase 8 adds merchant checkout resources, opaque payment links, QR references, wallet checkout, refund accounting, projected settlements, test API keys, and SSRF-protected webhook registration. Phase 9 adds platform roles, permissions, approvals, audit operations, compliance/risk monitoring, settings history, and system health. Financial capabilities remain sandbox-only.
## Phase 10 security sandbox

Phase 10 adds explainable AML/fraud monitoring, mock sanctions screening, device/session security records, and platform compliance case management. These features are sandbox-only. They do not provide regulatory clearance, submit reports, alter balances, or enable live financial operations.

Useful monitoring routes include /api/v1/platform/security/overview, /api/v1/platform/aml/alerts, /api/v1/platform/screening/matches, /api/v1/platform/fraud/alerts, and /api/v1/platform/compliance/cases. Access requires an authorized platform role and verified MFA. Customer security data is available under /api/v1/security/* and is owner-scoped.

## Phase 11 communication sandbox

Phase 11 adds in-app notifications, preferences, mock-safe external channels, customer and merchant support tickets, private attachment intake, SLA records, and separate dispute cases. External email/SMS/push are not reported as delivered without provider evidence. Support and dispute actions never change ledger balances or enable live money movement.

## Phase 12 reporting

Phase 12 adds read-only financial and merchant reporting over the immutable posted ledger. Trial balance, general ledger, profit/loss, balance sheet, wallet, transfer, FX, payment, settlement, reconciliation, and merchant analytics preserve currency separation and integer minor units. Reports and CSV exports are sandbox-only, private, permission-protected, checksum-tracked, and cannot modify balances.

## Phase 13 infrastructure

Phase 13 adds provider-neutral Docker Compose development infrastructure, production-oriented container files, Redis queue/cache configuration, correlation IDs, scheduler locking, CI workflows, Terraform module contracts, Prometheus/OpenTelemetry examples, backup/restore runbooks, and manual staging/production artifact workflows. Docker, Terraform, PostgreSQL, cloud credentials, and provider-specific deployment targets must be installed/configured before those checks can run. No Terraform apply or production deployment is performed by this repository.

## Phase 15 mobile foundation

The `mobile/` directory contains a Flutter customer-client foundation using Riverpod, GoRouter, Dio, Sanctum session cookies, secure-storage abstractions, local authentication, and English/Myanmar themes. Flutter/Dart tooling must be installed before mobile dependencies, tests, Android builds, or iOS builds can be verified. Mobile financial operations remain sandbox-only.

## Phase 17 enterprise operations

Phase 17 adds evidence-backed enterprise operations, governance records, deterministic feature rollout metadata, privacy-request tracking, incident records, release candidates, and privacy-conscious product analytics. Sensitive changes use existing maker-checker approval and step-up controls. No live financial capability or production deployment is enabled.

## Phase 18 regulatory readiness

Phase 18 adds evidence-backed jurisdiction and regulated-activity assessments, licensing readiness, regulatory obligations, prospective banking/PSP partner due diligence, environment-specific capability approvals, currency-separated safeguarding reconciliation, compliance assurance findings, private evidence metadata, controlled pilot plans, and blocked launch-readiness gates. These records do not represent legal authorization, a license, a banking partnership, safeguarding evidence, or regulatory approval. Live financial execution remains disabled.

Useful routes include `/api/v1/platform/regulatory/overview`, `/api/v1/platform/regulatory/partners`, `/api/v1/platform/safeguarding/overview`, and `/api/v1/platform/launch/readiness`. Access requires an authorized platform role and verified MFA. Launch assessment requests create independent approval work items and cannot activate production provider capabilities.

## Phase 19 controlled sandbox pilot operations

Phase 19 adds sandbox-only pilot programs, scoped cohorts and participants, backend eligibility checks, currency-aware pilot limits, activity and provider monitoring, reconciliation reviews, exceptions, evaluations, independent decision approvals and auditable closure requests.

Pilot operations cannot post ledger entries, activate production providers or enable live money movement. Missing telemetry is not treated as healthy, and currency totals are never combined implicitly.

## Phase 20 final audit and maintenance

Phase 20 extends the existing QA control center with evidence-backed module coverage, feature inventory, maintenance backlog, financial-integrity checks, infrastructure readiness and release-gate reporting. Missing evidence remains blocked or conditional; no metric is fabricated and no dashboard action enables live financial execution.

See `docs/final-audit.md`, `docs/financial-integrity.md`, `docs/release-readiness.md`, and `docs/maintenance-plan.md` for the audit scope and operating cadence.
