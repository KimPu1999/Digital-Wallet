# Payment links

Payment links store only a SHA-512 hash of a random opaque token. The raw token is returned once when the link is created and should be treated as a bearer secret. Links support fixed amounts, currency, expiry, usage limits, and single-use behavior. Usage is incremented under a row lock when a wallet checkout succeeds.

A link creates a checkout session; it never marks a payment complete. The session must pass the same customer authorization, step-up, idempotency, ledger posting, and audit controls as any other wallet checkout.
