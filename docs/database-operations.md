# Database Operations Audit

Migrations are additive and are tested by the local Laravel suite against an isolated in-memory database. The supported production-like database is PostgreSQL. Do not use `migrate:fresh`, `db:wipe`, or destructive resets against existing environments.

PostgreSQL verification must include migration execution, foreign-key checks, immutable ledger triggers, deferred journal balancing, currency separation, transaction isolation, lock contention, backup restore and reconciliation after restore.
