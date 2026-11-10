# Architecture

The platform is a sandbox-only React/Vite and Laravel application. The client is organized by app shell, features, pages, routes, services, and reusable UI primitives. The Laravel API is versioned under `/api/v1`, uses Sanctum SPA cookies, Form Requests, policies, resources, and a service boundary for audit events.

Phase 3 introduces a wallet aggregate owned by an explicit `wallet_owners` row. A customer or merchant owner has one wallet metadata record and can add unique `wallet_currency_accounts`. Currency records contain ISO code, symbol, exponent, ordering, and enabled state. Wallet creation is transactional, verifies email and country capability, applies KYC gates, records status history, and writes an audit event. Ownership is derived server-side; customer-supplied owner IDs are ignored.

The future financial core is intentionally not enabled. Wallet rows describe account metadata only; there is no mutable balance source of truth and no posting endpoint. A future ledger must use append-only double-entry records and derive balances from ledger entries.

## Boundaries

- Public: marketing and authentication entry points.
- Customer: own profile and wallet metadata only.
- Merchant: membership-scoped business resources.
- Platform admin: separately provisioned server-side and policy protected.
- Demo adapter: the only frontend source for sample figures; wallet API balances are explicitly null.

## Wallet lifecycle

`requested → active metadata → suspended/closed` is represented by wallet status and append-only status history. In Phase 3, creation only establishes account metadata. It does not activate deposits, withdrawals, transfers, FX, payment processing, or settlement. Phase 4 must connect currency accounts to ledger accounts and define authorization, idempotency, posting, reconciliation, and operational controls before any action is enabled.
