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
