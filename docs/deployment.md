# Deployment

## Local Docker

Requirements: Docker Engine with Compose v2. From the repository root:

```bash
docker compose build
docker compose up -d postgres redis
docker compose run --rm api php artisan migrate --force
docker compose run --rm api php artisan db:seed --force
docker compose up -d api frontend queue scheduler
```

The frontend is available at `http://localhost:5173` and the API at `http://localhost:8000`. PostgreSQL and Redis have no host port mappings in the local Compose file. This stack uses sandbox-only flags and local credentials; never reuse them outside development.

## Staging and production

`.github/workflows/staging.yml` and `production.yml` build immutable application artifacts and validate the Compose configuration. Staging is manually dispatched with a protected environment. Production is manually dispatched, requires the literal `RELEASE` confirmation and a protected GitHub environment approval. The workflows intentionally do not deploy cloud resources or run Terraform apply.

Production should run Laravel PHP-FPM behind a private-network Nginx/load balancer, separate queue and scheduler processes, private PostgreSQL/Redis, private object storage, and a cloud secret manager. Migrations follow expand/contract sequencing and are approved separately from application rollout.
