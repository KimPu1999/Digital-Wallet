# Phase 14 QA Plan

Phase 14 treats test output as evidence. A result is only considered passed when it is produced in an isolated environment, stored with its suite/category/status, and linked to an artifact or run record.

## Test layers

- Unit: domain services and deterministic money/risk rules.
- Feature/API: Laravel authorization, validation, state transitions and error responses.
- Integration: PostgreSQL transactions, locks, ledger projections, queues and Redis.
- Frontend: TypeScript/build checks and component tests when the framework is installed.
- E2E/accessibility: Playwright and WCAG checks in an isolated browser environment.
- Performance/recovery: bounded load tests and non-production restore drills.

## Safety rules

Tests use sandbox providers and isolated databases. No live payment, provider, production database, destructive reset, or public target is permitted. Missing tooling is recorded as unexecuted, never passed.

## Evidence model

`qa_test_runs` stores suite, category, environment, status, timing, summary, commit and artifact reference. `qa_findings` stores severity, status, evidence and remediation. Release gates remain blocked until required evidence is present and passing.

