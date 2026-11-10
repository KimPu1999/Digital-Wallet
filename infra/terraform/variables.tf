variable "environment" {
  type = string
  default = "development"
  validation {
    condition = contains(["development", "staging", "production"], var.environment)
    error_message = "Environment must be development, staging, or production."
  }
}
variable "cloud_provider" { type = string, default = "unset" }
variable "region" { type = string, default = "unset" }
variable "financial_execution_enabled" { type = bool, default = false }
