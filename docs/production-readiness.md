# Production readiness

This repository is not evidence of production readiness. Before production approval:

- Select and review cloud providers and regions.
- Implement provider-specific Terraform modules and run plan review.
- Configure secret manager, KMS/HSM, TLS, WAF, private networking, and IAM.
- Run PostgreSQL migration and concurrency tests against PostgreSQL.
- Complete backup/PITR restore drills and measure RPO/RTO.
- Configure actual OpenTelemetry, Prometheus, Grafana, Sentry, and alert routing.
- Scan and sign images, pin digests, and publish SBOMs.
- Validate queue, scheduler, webhook retry, and reconciliation operations.
- Obtain required legal, compliance, provider, and operational approvals.
- Keep live money movement feature flags disabled until a separately approved phase.

The admin infrastructure view is available at `/admin/infrastructure` and exposes only the existing safe health metadata endpoint.
