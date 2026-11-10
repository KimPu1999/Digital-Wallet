# Performance Testing

Performance tests must run only against an isolated sandbox deployment with test credentials and bounded intensity. Targets should include authentication, wallet reads, ledger activity, merchant payment reads, reports and queue processing.

Record requests/second, P50/P95/P99 latency, error rate, CPU/memory, database connections, slow queries and lock contention in `qa_performance_runs`. Do not infer capacity from a local development server.

No k6, Playwright or equivalent load/browser runner was available in the local environment, so no performance result is reported. The release gate remains blocked until a controlled run is stored.

