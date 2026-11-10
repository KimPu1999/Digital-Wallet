# Backup and restore

Backups must be encrypted, private, independently access-controlled, copied off-site, and tested in an isolated environment. PostgreSQL production configuration should include automated full backups, WAL archiving, point-in-time recovery, retention approved by compliance/legal, and encryption-key recovery procedures.

`ops/restore-validation.sh` performs a safe archive readability check and refuses targets that look like production. It does not restore into a database automatically. A complete restore drill must record backup timestamp, recovery point, restore duration, schema validation, ledger reconciliation, idempotency validation, provider-reference reconciliation, and operator approvals.

RPO/RTO targets are not claimed until restore drills are measured.
