# Feature Management

Existing `platform_feature_flags` are extended with category, rollout percentage, targeting metadata and schedule fields. Rollout assignment uses a deterministic SHA-256 subject/flag bucket. Frontend visibility is never a security boundary; backend authorization and eligibility remain authoritative.

Phase 17 change requests submit approval work and do not mutate flags directly.
