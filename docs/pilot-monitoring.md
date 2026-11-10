# Pilot Monitoring

Monitoring endpoints expose pilot activity events, monitoring snapshots, sandbox provider status, operational exceptions and selected queue/webhook telemetry where available. Activity views are read-only and paginated at ten rows per page.

Unknown provider outcomes must remain unknown until reconciled. Missing telemetry is not evidence of success. Failed jobs and webhook events must follow existing idempotent retry and reconciliation policies.
