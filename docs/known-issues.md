# Known Issues and Verification Gaps

1. PostgreSQL was not running on `127.0.0.1:5432`; PostgreSQL migrations, locking tests and restore validation were not executed.
2. Docker and Terraform CLIs were unavailable; container builds and infrastructure validation were not executed.
3. Playwright, Vitest, k6 and Redis CLI were unavailable; browser, frontend unit, load and Redis-specific tests were not executed.
4. Composer/npm vulnerability audits and secret/container scans require their respective network/tooling and are not represented as passed.
5. Frontend production build reports a large bundle warning (>500 kB) and existing Zod/Rollup annotation warnings; this is a performance follow-up, not a financial defect.
6. QA evidence tables start empty by design. Release gates remain blocked until CI or approved isolated runs ingest artifacts.

