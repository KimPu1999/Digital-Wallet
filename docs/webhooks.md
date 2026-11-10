# Webhook security

The webhook route is unauthenticated because providers cannot use a customer session. It is still protected by:

1. Provider-specific signature verification over the raw request body.
2. Timestamp tolerance and replay rejection where supported.
3. Provider account and event type validation.
4. Unique provider plus event-id deduplication.
5. Encrypted raw-body storage for investigation without logging secrets.
6. Amount, currency, intent-reference, and state validation.
7. Idempotent provider transaction creation and settlement.

The sandbox mock signature is HMAC-SHA256(timestamp + "." + raw_body, PAYMENT_SANDBOX_WEBHOOK_SECRET) sent as X-Mock-Signature with X-Mock-Timestamp. This is a development mechanism, not a real provider credential.

A webhook is evidence of a verified provider event, not by itself proof that funds are finally settled. Settlement is recorded separately, and unknown/out-of-order events create or preserve reconciliation work.
