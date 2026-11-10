variable "environment" { type = string }
output "contract" { value = { module = "monitoring", environment = var.environment, prometheus = true, grafana = true, otel = true, sentry = true } }
