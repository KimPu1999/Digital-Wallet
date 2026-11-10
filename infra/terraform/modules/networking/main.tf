variable "environment" { type = string }
variable "region" { type = string }
output "contract" { value = { module = "networking", environment = var.environment, region = var.region, database_private = true, redis_private = true } }
