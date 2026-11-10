# Infrastructure

The provider-neutral Terraform contract is under `infra/terraform`. It defines boundaries for networking, application hosting, PostgreSQL, Redis, object storage, load balancing, TLS, secrets, monitoring, and backups. A cloud provider must be selected before resources or credentials are added.

Expected topology:

```text
Public HTTPS/WAF -> load balancer -> Nginx -> Laravel PHP-FPM
                                      |-> private Redis queue/cache
                                      |-> private PostgreSQL
                                      |-> private object storage
```

Only HTTPS ingress is public. PostgreSQL and Redis remain private. Admin access uses an approved identity path, MFA, and least-privilege security groups. Financial feature flags default disabled in every environment.
