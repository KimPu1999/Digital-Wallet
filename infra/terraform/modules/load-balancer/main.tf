variable "environment" { type = string }
output "contract" { value = { module = "load-balancer", environment = var.environment, https_only = true, waf = "provider-dependent" } }
