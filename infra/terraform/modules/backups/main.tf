variable "environment" { type = string }
output "contract" { value = { module = "backups", environment = var.environment, encrypted = true, offsite_copy = true, restore_testing = true } }
