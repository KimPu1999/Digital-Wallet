# Architecture

Phase 1 is a sandbox-only foundation. The React/Vite client is organized by app shell, features, pages, routes, services, and reusable UI primitives. The Laravel API is versioned under `/api/v1`, uses Sanctum SPA cookies, Form Requests, policies, and a service boundary for audit events.

The future financial core is intentionally not enabled. Wallet rows describe account metadata only; there is no mutable balance source of truth and no posting endpoint. A future ledger must use append-only double-entry records and derive balances from ledger entries.

## Boundaries

- Public: marketing and authentication entry points.
- Customer: own profile and wallet metadata only.
- Merchant: membership-scoped business resources.
- Platform admin: separately provisioned server-side and policy protected.
- Demo adapter: the only frontend source for sample figures.
