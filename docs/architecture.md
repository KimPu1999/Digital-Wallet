# Architecture

The platform is a sandbox-only React/Vite and Laravel application. The client is organized by app shell, features, pages, routes, services, and reusable UI primitives. The Laravel API is versioned under `/api/v1`, uses Sanctum SPA cookies, Form Requests, policies, resources, and a service boundary for audit events.

Phase 3 introduces a wallet aggregate owned by an explicit `wallet_owners` row. A customer or merchant owner has one wallet metadata record and can add unique `wallet_currency_accounts`. Currency records contain ISO code, symbol, exponent, ordering, and enabled state. Wallet creation is transactional, verifies email and country capability, applies KYC gates, records status history, and writes an audit event. Ownership is derived server-side; customer-supplied owner IDs are ignored.

Phase 4 adds a separate ledger boundary under `app/Domain/Ledger` and financial services. The ledger owns immutable journal entries; projections and holds are derived operational state. The posting boundary is internal and has no customer or merchant money-movement route. Reconciliation and outbox records support future asynchronous operations without implying an external provider integration.

The future financial core is intentionally not enabled. Wallet rows describe account metadata only; there is no mutable balance source of truth and no posting endpoint. A future ledger must use append-only double-entry records and derive balances from ledger entries.

## Boundaries

- Public: marketing and authentication entry points.
- Customer: own profile and wallet metadata only.
- Merchant: membership-scoped business resources.
- Platform admin: separately provisioned server-side and policy protected.
- Demo adapter: the only frontend source for sample figures; wallet API balances are explicitly null.

## Wallet lifecycle

`requested → active metadata → suspended/closed` is represented by wallet status and append-only status history. In Phase 3, creation only establishes account metadata. It does not activate deposits, withdrawals, transfers, FX, payment processing, or settlement. Phase 4 must connect currency accounts to ledger accounts and define authorization, idempotency, posting, reconciliation, and operational controls before any action is enabled.
## Phase 5 FX boundary

FX is a bounded domain above the Phase 4 ledger. Provider adapters create rate snapshots; the quote service creates immutable, expiring quotes; the conversion service turns an accepted sandbox quote into a two-currency balanced ledger journal. Wallet metadata and projections remain separate from quote and conversion records. The customer API cannot submit arbitrary ledger lines.

## Phase 6 transfer boundary

Transfers are a separate command domain above wallets, holds, ledger, FX, compliance, and provider ports. Beneficiary details are encrypted; quotes are server-authoritative; the transfer state machine is explicit; limit usage and step-up challenges are persisted; mock screening/provider adapters are deterministic and sandbox-labeled. Only the transfer service can reserve, capture, or release holds and invoke the internal ledger posting boundary.

## Phase 7 payment boundary

Payments are a separate orchestration domain above the existing wallet, ledger, hold, compliance, and audit boundaries. A payment intent is not a ledger transaction. A provider attempt is not settlement. Only PaymentSettlementService can bridge a verified provider transaction to the internal ledger, and it uses the existing idempotent LedgerPostingService or WalletHoldService.

The provider port is adapter-based: registry, capability/routing, adapter attempt, verified webhook, provider transaction, then settlement/reconciliation. Production provider adapters are disabled by configuration and no customer route accepts arbitrary provider or ledger identifiers. Merchant payment intents are scoped by merchant memberships and remain non-live.

## Phase 8 merchant commerce boundary

Merchant checkout resources sit above payment intents and the ledger. Payment links and QR records expose only opaque public tokens; hashes are stored server-side. Checkout sessions expire and become paid only after a verified wallet checkout posts a balanced journal. Refunds are new compensating journals and cannot edit the original payment. Settlement reporting is an internal projection and remains `projected` until matching provider evidence exists.

Hosted checkout, API keys, and merchant webhooks are integration boundaries, not proof of payment. Provider-hosted card collection is disabled in the sandbox. API keys are hashed, scoped, revocable, and merchant-isolated. Webhook URLs pass SSRF checks and signing secrets are encrypted; delivery is represented by queued records until a reviewed worker is enabled.

## Phase 9 platform operations boundary

Platform administration is separated from customer and merchant tenancy. A platform membership maps a staff user to a platform role and permissions. The admin middleware checks the membership, active account, and verified MFA; sensitive service methods additionally consume a one-time step-up challenge. Approvals, security events, platform audit events, risk cases, feature flags, and versioned settings have separate records and are not mixed into wallet balances or merchant data.
## Phase 10 security and compliance monitoring

The monitoring domain wraps existing financial data without becoming a financial posting path. Risk rules are versioned, evaluations are idempotent, alerts are explainable, screening providers are adapters, and compliance cases are auditable. Monitoring APIs are read-only for financial records; sensitive case decisions require platform roles and MFA.
