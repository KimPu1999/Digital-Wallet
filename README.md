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
