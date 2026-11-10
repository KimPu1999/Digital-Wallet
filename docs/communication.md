# Communication channels (Phase 11)

The channel interface currently includes in-app, email, SMS, and push adapters. In-app delivery is database-backed. Email uses the local/mail configuration boundary and remains mock-queued until an approved provider is configured. SMS and push are sandbox adapters with no credentials or external network calls.

Queue workers, provider retries, consent, phone verification, unsubscribe handling, and provider-specific delivery evidence must be completed before production communications.
