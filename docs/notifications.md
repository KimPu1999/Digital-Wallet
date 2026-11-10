# Notifications (Phase 11)

NotificationEventService writes an in-app notification and event receipt in one transaction. The unique event key prevents duplicate notices. Delivery rows are evidence of an adapter attempt; mock-queued email, SMS, and push rows are explicitly not delivery confirmation.

Security and mandatory service notices bypass optional preferences. Marketing and product updates require separate opt-in preferences. Templates are versioned by key, locale, and version, with HTML-escaped variable interpolation.
