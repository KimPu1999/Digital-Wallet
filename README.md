# Digital Wallet Platform

Phase 1 foundation for a professional digital wallet product. This repository is deliberately sandbox-only: displayed balances and transactions are demo data, and real deposits, withdrawals, transfers, or payment processing are not enabled.

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

Set the PostgreSQL values in `backend/.env` before migrating. Do not use destructive reset commands against an existing database.

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

The environment used to prepare this foundation could not resolve Packagist, so Composer dependency installation and Laravel runtime tests must be rerun in a network-enabled environment. PostgreSQL client tooling is present, but no database was provisioned or modified by this scaffold.

## Phase 2 readiness

- Provision a clean PostgreSQL database and run migrations.
- Install Laravel/Sanctum dependencies and configure stateful domains and CORS.
- Add framework bootstrap files from the compatible Laravel skeleton.
- Add role/permission middleware and server-side admin provisioning.
- Add real test fixtures using a separate test database.
- Design and review the double-entry ledger before enabling any financial endpoint.

## Phase 2 commands

```bash
cd backend
php artisan db:seed
php artisan admin:provision admin@example.com "Platform Admin" --role=platform_super_admin
php artisan test
```

Phase 2 adds country eligibility, separate merchant/admin authentication endpoints, email/password recovery hooks, MFA enrollment, private KYC document intake, and compliance schema. Financial capabilities remain disabled by the default seeder.
