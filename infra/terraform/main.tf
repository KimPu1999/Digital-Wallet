module "networking" {
  source = "./modules/networking"
  environment = var.environment
  region = var.region
}
module "application" {
  source = "./modules/application"
  environment = var.environment
  financial_execution_enabled = var.financial_execution_enabled
}
module "postgresql" {
  source = "./modules/postgresql"
  environment = var.environment
}
module "redis" {
  source = "./modules/redis"
  environment = var.environment
}
module "object_storage" {
  source = "./modules/object-storage"
  environment = var.environment
}
module "load_balancer" {
  source = "./modules/load-balancer"
  environment = var.environment
}
module "tls" {
  source = "./modules/tls"
  environment = var.environment
}
module "secrets" {
  source = "./modules/secrets"
  environment = var.environment
}
module "monitoring" {
  source = "./modules/monitoring"
  environment = var.environment
}
module "backups" {
  source = "./modules/backups"
  environment = var.environment
}
