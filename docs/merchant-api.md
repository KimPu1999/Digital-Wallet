# Merchant API

Merchant routes are under `/api/v1/merchants/{merchant}` and require a Sanctum session plus a membership role. Link, checkout session, QR, refund, settlement, analytics, API key, and webhook resources are merchant-scoped. No route accepts arbitrary ledger lines or direct balance changes.

Test API keys use the `dw_test_` prefix. Only a SHA-256 hash is stored. The raw key is returned once at creation, has explicit scopes, and can be revoked. API key authentication middleware is intentionally not enabled for live traffic in this sandbox; it must be reviewed before use outside a test environment.

Webhook endpoint registration requires HTTPS and rejects credentials, private or loopback destinations, unsafe schemes, and explicit ports. Signing secrets are encrypted. Deliveries are queued records with event subscriptions and duplicate constraints; a production worker must add signed HTTP delivery, retry backoff, response handling, and replay controls.
