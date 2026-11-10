# Disaster recovery

## Database failure

Declare the database incident, stop financial execution flags, preserve logs, fail over only through the approved provider procedure, validate schema and ledger reconciliation, and review pending provider outcomes before resuming any sandbox workflow.

## Application or Redis outage

Keep financial execution disabled, restore healthy application/worker capacity, inspect failed jobs and idempotency records, and reconcile unknown payment/webhook outcomes before retrying.

## Storage or credential compromise

Revoke affected credentials, stop access, preserve audit evidence, rotate through the secret manager, validate private object access, and require security approval before re-enabling non-financial operations.

## Failed deployment or corruption

Stop rollout, route to the last verified immutable artifact, do not automatically reverse irreversible migrations, restore data only in an approved isolated process, and run ledger/reconciliation validation before service recovery.

## Phase 20 evidence requirement

Recovery drills are evidence records, not promises. A valid drill must restore into an isolated environment, validate schema and ledger balances, check idempotency and provider references, record recovery point and duration, and reconcile before any sandbox service resumes. No RPO/RTO target is considered met without measured restore evidence.
