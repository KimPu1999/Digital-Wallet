# Digital Wallet Platform

Phase 6 foundation for a professional digital wallet product. This repository is deliberately sandbox-only: ledger balances, FX conversions, and transfers are internal/demo state, and real deposits, withdrawals, external FX, bank payouts, or payment processing are not enabled.

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

## Phase 3 readiness

- Provision a clean PostgreSQL database and run migrations.
- Install Laravel/Sanctum dependencies and configure stateful domains and CORS.
- Configure Sanctum stateful domains, secure cookies, CORS, and PostgreSQL.
- Add real test fixtures using a separate test database.
- Review merchant membership fixtures and admin policy coverage.
- Design and review the double-entry ledger before enabling any financial endpoint.

## Phase 3 commands

```bash
cd backend
php artisan test
```

Phase 2 adds country eligibility, separate merchant/admin authentication endpoints, email/password recovery hooks, MFA enrollment, private KYC document intake, and compliance schema. Phase 3 adds multi-currency wallet metadata, ownership policies, merchant-scoped wallet context, admin monitoring, and a customer wallet-management UI. Financial capabilities remain disabled by the default seeder.
