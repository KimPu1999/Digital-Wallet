# Performance Audit

Performance evidence is represented by `qa_performance_runs` with target, request rate, P50/P95/P99 latency and error rate. The quality center reports recorded artifacts only; it does not fabricate latency or throughput.

Before production approval, run bounded PostgreSQL and Redis load tests, inspect query plans and lock contention, measure queue latency, and record web/mobile startup and rendering results in an isolated environment.
