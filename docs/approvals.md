# Admin approvals

Maker-checker requests are stored in `admin_approval_requests` with an action, resource, reason, idempotency key, expiry, and required decision count. Decisions are stored separately. The requester cannot approve their own request. Sensitive decisions require verified MFA plus a one-time sandbox step-up challenge. Dual approval remains pending until the configured number of distinct approvers approve.

An approval authorizes only the named action and resource. It does not bypass wallet ownership, ledger balance, country, KYC/KYB, provider, or sandbox controls.
