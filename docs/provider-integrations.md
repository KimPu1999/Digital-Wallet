# Provider integrations

All providers implement PaymentProviderInterface:

- code and capability declaration
- provider operation submission
- provider-specific webhook verification using the raw request body
- normalized webhook event output

PaymentProviderRegistry resolves an adapter from the database provider code. ProviderCapabilityService and ProviderRoutingService filter by operation, country, currency, payment method, provider health, account status, and sandbox environment. Routing never falls back to an arbitrary provider.

MockPaymentProvider is deterministic and sandbox-only. StripePaymentAdapter, WisePlatformAdapter, and AirwallexAdapter intentionally expose no unsupported sandbox operation. Official SDK/API work, credentials, account approval, hosted collection, and production enablement are Phase 8 work and must be based on current provider documentation.

Provider credentials belong in encrypted server-side configuration or a secrets manager. They must not be committed to the repository or exposed through Vite variables.
