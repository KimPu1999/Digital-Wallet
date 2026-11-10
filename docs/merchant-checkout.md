# Merchant checkout

Phase 8 adds merchant-scoped sandbox commerce resources above the Phase 7 payment and Phase 4 ledger boundaries. A checkout session contains an opaque, hashed public token, an order reference, a BIGINT minor-unit amount, an ISO currency, an expiry, allowed methods, and a branding snapshot.

The only executable checkout in this phase is authenticated customer-to-merchant wallet checkout. It verifies customer email, wallet ownership, currency, step-up challenge, available funds, merchant eligibility, and idempotency before posting a balanced liability-to-liability ledger journal. The session becomes paid only after that journal succeeds.

Hosted provider checkout is represented in the API and UI, but configured provider adapters do not enable live card collection. Raw card numbers and CVV are never accepted or stored. Provider authorization is not treated as settlement.

Merchant access is server-side and membership-scoped. Owner and manager roles approve refunds; finance can request refunds; developer manages integration settings; analyst has read-only visibility. All responses are marked sandbox-only.
