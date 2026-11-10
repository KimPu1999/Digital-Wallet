# Infrastructure cost planning

No exact cloud prices are included because provider, region, retention, traffic, and support tier are not selected. Capacity planning should model these drivers:

| Environment | Compute | Database | Redis | Storage/backups | Observability |
|---|---|---|---|---|---|
| Development | Single local Compose stack | Local persistent volume | Local persistent volume | Local/private | Local logs |
| Staging | Small redundant API/worker capacity | Managed PostgreSQL small tier | Managed Redis small tier | Private object storage and test backups | Reduced metrics/log retention |
| Production | Multi-zone API/worker capacity | Managed PostgreSQL HA, replicas, PITR | Managed Redis HA | Encrypted off-site backups and private objects | Prometheus/Grafana/OTel/Sentry and alerting |

Primary cost drivers are database IOPS/storage, HA/replica capacity, queue volume, backup retention, egress, log/trace cardinality, and security/WAF services. Measure staging load before setting production capacity or RPO/RTO targets.
