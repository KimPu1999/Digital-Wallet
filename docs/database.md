# Database foundation

PostgreSQL is the target datastore. Initial migrations cover users, roles, permissions, role assignments, customer profiles, merchants, merchant memberships, wallets, currencies, and audit logs. UUID primary keys are used for domain records; framework-managed user IDs remain compatible with a standard Laravel install.

Wallets contain `label`, `wallet_type`, `status`, owner, and currency metadata. They deliberately do not contain a financial balance column.

Reserved future tables: `ledger_accounts`, `ledger_transactions`, `ledger_entries`, `transfers`, `deposits`, `withdrawals`, `idempotency_keys`, and `reconciliation_runs`. These require a separate design review before migration.

Phase 2 adds `login_activities`, `active_sessions`, `mfa_methods`, `mfa_recovery_codes`, `mfa_challenges`, `countries`, `country_capabilities`, `payment_corridors`, `jurisdiction_policies`, `kyc_cases`, `kyc_requirements`, `kyc_documents`, `kyc_reviews`, `compliance_events`, `merchant_verifications`, and `beneficial_owners`. Financial capabilities default to disabled.
