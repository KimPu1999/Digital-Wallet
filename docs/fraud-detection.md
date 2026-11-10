# Fraud detection (Phase 10)

The fraud foundation records explainable signals such as failed-authentication velocity and new-device events. FraudDetectionService delegates deterministic decisions to the versioned risk engine and creates deduplicated alerts. No machine-learning prediction is claimed or used.

Sensitive operations must continue to use existing MFA step-up controls. Device metadata is minimal, hashed identifiers are not returned to the client, and customers can only read their own security records.
