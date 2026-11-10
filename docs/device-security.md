# Device and session security (Phase 10)

Device registrations store a hashed device key, low-sensitivity platform metadata, lifecycle status, and last-seen time. Authentication security events capture severity, correlation, IP metadata, and safe event metadata without secrets.

Customer security routes derive ownership from the Sanctum-authenticated user and cannot accept an arbitrary user ID. Session revocation applies only to that user’s active session. Retention, privacy notices, and production device-risk policy must be finalized before launch.
