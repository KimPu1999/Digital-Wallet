variable "environment" { type = string }
output "contract" { value = { module = "secrets", environment = var.environment, secret_manager = "provider-dependent", plaintext_outputs = false } }
