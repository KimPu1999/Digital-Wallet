# Transfer compliance integration points

The transfer workflow creates five sandbox screening records for sanctions, PEP, beneficiary risk, country risk, and velocity. MockTransferScreeningService returns a clearly labeled development result and never represents a real compliance decision.

Production integration must add sanctions and PEP vendor adapters, versioned country/corridor policy, KYC/KYB tier resolution, velocity monitoring, manual review with dual approval, suspicious activity escalation, provider callback authentication, and retention controls.

Country capabilities and seeded transfer limits are feature configuration only. They do not assert regulatory approval or legal limits.
