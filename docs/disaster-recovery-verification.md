# Disaster Recovery Verification

Recovery must be tested in a non-production environment. The existing `ops/restore-validation.sh` refuses production-looking targets and requires `CONFIRM_NON_PRODUCTION=YES`. It validates a supplied backup reference and target database before application-level checks are run.

Record schema validation, ledger reconciliation, idempotency validation, restore duration and recovery point in `qa_recovery_drills`. A run is not successful unless all required financial checks pass.

PostgreSQL was unavailable locally, so no restore drill was executed. RPO/RTO targets therefore remain unverified.

