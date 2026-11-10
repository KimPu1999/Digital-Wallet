variable "environment" { type = string }
output "contract" { value = { module = "object-storage", environment = var.environment, private = true, encryption = true, versioning = true } }
