# Terraform contract

This directory defines provider-neutral module boundaries for networking, application hosting, PostgreSQL, Redis, private object storage, load balancing, TLS, secret management, monitoring, and backups. No cloud provider has been selected and no provider credentials or resources are declared.

Run only validation until a provider-specific implementation and approval exist:

```bash
terraform -chdir=infra/terraform init
terraform -chdir=infra/terraform validate
terraform -chdir=infra/terraform plan -var-file=terraform.tfvars.example
```

`terraform apply` is intentionally not part of the project workflow.
