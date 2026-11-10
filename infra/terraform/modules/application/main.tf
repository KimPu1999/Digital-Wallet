variable "environment" { type = string }
variable "financial_execution_enabled" { type = bool }
output "contract" { value = { module = "application", environment = var.environment, financial_execution_enabled = var.financial_execution_enabled, non_root = true } }
