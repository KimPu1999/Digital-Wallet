# Database foundation

PostgreSQL is the target datastore. Initial migrations cover users, roles, permissions, role assignments, customer profiles, merchants, merchant memberships, wallets, currencies, and audit logs. Phase 3 adds `wallet_owners`, `wallet_currency_accounts`, `wallet_preferences`, and `wallet_status_history`. UUID primary keys are used for domain records; framework-managed user IDs remain compatible with a standard Laravel install.

Wallets contain `label`, `nickname`, `wallet_type`, `status`, owner, and legacy/default currency metadata. `wallet_owners` explicitly separates customer and merchant ownership. `wallet_currency_accounts` enforces one account per wallet/currency pair. `wallet_preferences` stores preferred display currency and locale. `wallet_status_history` records lifecycle transitions and actors. These tables deliberately do not contain a financial balance column.

The currency catalog is seeded with USD, EUR, GBP, SGD, THB, and MMK. `decimals` is the currency exponent and `display_order` controls presentation only. Enabled configuration must never be interpreted as country eligibility or payment approval.

Reserved future tables: `ledger_accounts`, `ledger_transactions`, `ledger_entries`, `transfers`, `deposits`, `withdrawals`, `idempotency_keys`, and `reconciliation_runs`. These require a separate design review before migration.

Phase 2 adds `login_activities`, `active_sessions`, `mfa_methods`, `mfa_recovery_codes`, `mfa_challenges`, `countries`, `country_capabilities`, `payment_corridors`, `jurisdiction_policies`, `kyc_cases`, `kyc_requirements`, `kyc_documents`, `kyc_reviews`, `compliance_events`, `merchant_verifications`, and `beneficial_owners`. Financial capabilities default to disabled.

Phase 4 should add `ledger_accounts`, `ledger_transactions`, `ledger_entries`, `transfers`, `deposits`, `withdrawals`, `idempotency_keys`, and `reconciliation_runs` only after defining immutable amount storage, currency exponent rules, posting invariants, reversals, holds, settlement, and reconciliation.
