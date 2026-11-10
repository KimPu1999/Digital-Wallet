# Monitoring and observability

Prometheus scrape and alert examples are under `infra/observability`. Grafana dashboard panels cover API rate/latency, queue backlog, reconciliation exceptions, and backup freshness. OpenTelemetry collector configuration is provided as a provider-neutral starting point.

The application already exposes safe health metadata without credentials. Full application metrics, tracing SDKs, Sentry DSNs, exporters, and alert routing require environment-specific integration and must be verified against actual telemetry. Dashboard JSON is not evidence that a metric is currently being collected.

Use correlation IDs from `X-Correlation-ID` in logs and incident investigations. Never record passwords, tokens, raw account details, identity documents, or webhook secrets.
