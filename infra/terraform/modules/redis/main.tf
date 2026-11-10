variable "environment" { type = string }
output "contract" { value = { module = "redis", environment = var.environment, private = true, auth = true, persistence = true } }
