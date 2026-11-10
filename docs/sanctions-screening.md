# Sanctions and PEP screening (Phase 10)

ScreeningProviderInterface isolates screening providers. MockScreeningProvider is the only configured adapter and is explicitly marked sandbox. It supports no_match, potential_match, timeout, and error test modes.

Name similarity is not a confirmed match. Potential matches require analyst review and remain in screening_matches. Provider failures set the request to failed; the risk engine produces review rather than clearance. No regulatory submission or automated clearance is implemented.
