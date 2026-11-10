# Platform Super Admin Control Center

Phase 9 adds a separate platform administration boundary under `/api/v1/admin/platform`. Access requires an active `platform_admin_memberships` record, an assigned platform role, a matching permission, an active user account, and verified MFA. Frontend route visibility is not authorization.

Roles are Platform Super Admin, Compliance Admin, Finance Operations, Risk Analyst, Support Agent, and Read-Only Auditor. Permissions are explicit and server-checked. Financial views are read-only; no administrator can edit a balance or delete posted ledger entries.

The dashboard reports sandbox aggregates by currency. USD, EUR, GBP, SGD, THB, and MMK are never combined without an explicit FX conversion. User and merchant directories are paginated and sensitive identity documents are not returned by directory endpoints.
