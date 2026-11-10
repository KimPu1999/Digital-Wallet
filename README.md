# Digital Wallet Platform

Phase 3 foundation for a professional digital wallet product. This repository is deliberately sandbox-only: wallet balances are `null` until the Phase 4 ledger, and real deposits, withdrawals, transfers, FX, or payment processing are not enabled.

## Requirements

- Node.js 20+ and npm
- PHP 8.2+
- Composer
- PostgreSQL 15+

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
