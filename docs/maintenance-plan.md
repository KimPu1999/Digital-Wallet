# Long-Term Maintenance Plan

| Cadence | Review |
|---|---|
| Every change | TypeScript build, Laravel tests, authorization and financial regression checks |
| Weekly | Failed jobs, webhook backlog, reconciliation exceptions, open security findings |
| Monthly | Composer/npm/Flutter dependency review, secret rotation review, query and queue performance |
| Quarterly | Access review, backup restore drill, incident/postmortem review, documentation review |
| Before release | Protected CI artifact, PostgreSQL validation, security checks, release approval and rollback review |

Maintenance items are stored in `maintenance_items` with owner, priority, due date, status and evidence reference.
