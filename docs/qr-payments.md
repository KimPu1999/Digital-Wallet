# QR payments

Static and dynamic QR records use opaque hashed references with explicit status and optional expiry. A dynamic QR points to a checkout session; a static QR is only a merchant reference and requires a later session creation flow. QR generation never creates a successful payment.

The implementation does not claim EMVCo or national QR compliance. Such a claim requires implementing the applicable specification, signing rules, merchant-presented/customer-presented flows, certification, and security review.
