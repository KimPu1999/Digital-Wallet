variable "environment" { type = string }
output "contract" { value = { module = "tls", environment = var.environment, managed_certificates = true, minimum_tls = "1.2" } }
