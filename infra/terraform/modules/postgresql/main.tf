variable "environment" { type = string }
output "contract" { value = { module = "postgresql", environment = var.environment, private = true, tls = true, backups = true, pitr = true } }
