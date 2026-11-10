# System Architecture Inventory

The platform is organized as a React/Vite web client, Laravel API/domain services, PostgreSQL persistence, Redis/queue infrastructure, provider adapters, and a Flutter mobile client. Financial truth is maintained by the immutable, currency-specific ledger; wallet projections and reports are derived read models.

Customer, merchant and platform-admin routes are separate. Platform operations use Sanctum, platform permissions, MFA/step-up checks, audit logging and maker-checker approvals. Provider, payment, FX, transfer, AML, support, reporting and pilot domains remain sandbox-scoped.

Infrastructure templates are provider-neutral. Docker, CI, observability, backups and Terraform files describe deployable contracts but do not prove that cloud services, restore drills, PostgreSQL, Redis or production credentials are available.
