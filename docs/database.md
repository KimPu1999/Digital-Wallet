# Database foundation

PostgreSQL is the target datastore. Initial migrations cover users, roles, permissions, role assignments, customer profiles, merchants, merchant memberships, wallets, currencies, and audit logs. Phase 3 adds `wallet_owners`, `wallet_currency_accounts`, `wallet_preferences`, and `wallet_status_history`. UUID primary keys are used for domain records; framework-managed user IDs remain compatible with a standard Laravel install.

Wallets contain `label`, `nickname`, `wallet_type`, `status`, owner, and legacy/default currency metadata. `wallet_owners` explicitly separates customer and merchant ownership. `wallet_currency_accounts` enforces one account per wallet/currency pair. `wallet_preferences` stores preferred display currency and locale. `wallet_status_history` records lifecycle transitions and actors. These tables deliberately do not contain a financial balance column.

The currency catalog is seeded with USD, EUR, GBP, SGD, THB, and MMK. `decimals` is the currency exponent and `display_order` controls presentation only. Enabled configuration must never be interpreted as country eligibility or payment approval.

Reserved future tables: `ledger_accounts`, `ledger_transactions`, `ledger_entries`, `transfers`, `deposits`, `withdrawals`, `idempotency_keys`, and `reconciliation_runs`. These require a separate design review before migration.

Phase 2 adds `login_activities`, `active_sessions`, `mfa_methods`, `mfa_recovery_codes`, `mfa_challenges`, `countries`, `country_capabilities`, `payment_corridors`, `jurisdiction_policies`, `kyc_cases`, `kyc_requirements`, `kyc_documents`, `kyc_reviews`, `compliance_events`, `merchant_verifications`, and `beneficial_owners`. Financial capabilities default to disabled.

Future provider and movement tables such as `transfers`, `deposits`, and `withdrawals` remain intentionally reserved until immutable amount storage, currency exponent rules, posting invariants, settlement, and compliance controls are approved.

The Phase 4 migration now implements the ledger tables: `ledger_accounts`, `ledger_transactions`, `ledger_entries`, `ledger_reversals`, `balance_projections`, `wallet_holds`, `idempotency_keys`, `reconciliation_runs`, `reconciliation_items`, `financial_audit_events`, and `outbox_events`. Posted ledger entries, reversals, and financial audit events have PostgreSQL immutability triggers. A deferred PostgreSQL constraint trigger validates journal balance at the posted status boundary. SQLite migration runs intentionally skip PostgreSQL triggers and are not a substitute for financial locking tests.
## Phase 5 tables

fx_rate_providers, fx_pairs, fx_rate_snapshots, and fx_fee_rules hold provider configuration and observed quote inputs. fx_quotes is immutable after issuance. fx_conversions is the one-time execution record and links to a posted ledger transaction. fx_conversion_legs preserves the currency-specific accounting legs. fx_audit_events and fx_reconciliation_items support operational review.

Rates use DECIMAL(36,18). Monetary values use BIGINT minor units. There is no FX balance column and no floating-point monetary calculation.

Phase 6 adds beneficiaries and beneficiary_accounts, transfer_quotes, transfers, transfer_events, transfer_screenings, transfer_attempts, transfer_limits, transfer_limit_usages, transfer_receipts, and transfer_step_up_challenges. Encrypted beneficiary details are stored as ciphertext; only a masked suffix is presentation metadata. Transfer amounts are BIGINT minor units and external recipient amounts are not wallet balances.
## Phase 7 payment tables

payment_providers, provider_accounts, provider_capabilities, provider_routing_rules, and provider_health_events describe provider configuration without plaintext credentials. payment_intents, payment_attempts, and provider_transactions record lifecycle and external observations. payment_webhook_events stores encrypted raw bodies and processing state. settlement_batches and settlement_items match provider settlement to ledger references. payment_reconciliation_items stores exceptions and never silently changes balances.

Payment amounts are BIGINT minor units with explicit ISO currency codes. The existing ledger remains the financial source of truth; no payment table contains a mutable customer balance.

## Phase 8 merchant tables

`merchant_payment_links`, `checkout_sessions`, `merchant_qr_codes`, `refund_requests`, `merchant_settlements`, `merchant_api_keys`, `merchant_webhook_endpoints`, `merchant_webhook_deliveries`, and `merchant_checkout_settings` are merchant-scoped. Public link, checkout, and QR values are stored as hashes; amounts are BIGINT minor units; settlement status defaults to `projected`. Refund requests reference a payment intent and, after approval, a compensating ledger transaction. No table is a balance source of truth.

## Phase 9 platform operations tables

`platform_admin_memberships`, `platform_roles`, `platform_permissions`, and `platform_role_permissions` provide the platform staff authorization graph. `admin_approval_requests` and `admin_approval_decisions` implement expiring maker-checker decisions. `admin_security_events` and `platform_audit_events` separate security/audit history from financial audit events. `risk_cases` and `risk_case_events` record investigation timelines. `platform_feature_flags`, `platform_settings`, and `platform_settings_history` keep operational configuration versioned. Financial flags are seeded disabled and no platform table stores a mutable wallet balance.
## Phase 10 tables

The security migration adds customer risk profiles, versioned risk rules/evaluations, alerts, screening requests/matches, compliance cases/events/evidence, device registrations, authentication security events, regulatory report drafts, and security policy versions. It intentionally reuses Phase 9 risk_cases, platform approvals, and platform audit events rather than duplicating them.

Sensitive references are hidden from API serialization. Foreign keys, scoped unique keys, and indexes protect ownership and idempotency boundaries. Posted ledger tables are unchanged.

## Phase 11 tables

The communication migration adds Laravel-compatible notifications, preferences, templates, deliveries, event receipts, support tickets/messages/attachments/events/assignments, versioned SLA policies, and dispute cases/events. The notification event receipt and support message client key are idempotency boundaries.
