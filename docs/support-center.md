# Support center (Phase 11)

Support tickets are scoped to a customer or merchant. Customer-visible messages and internal agent notes use separate visibility values. Ticket state transitions are validated by SupportTicketService, assignments are recorded, and SLA due times come from versioned sandbox policies.

Attachments are stored on the private disk, validated by MIME/size rules, marked pending for malware scanning, and never exposed as executable public files. The current API returns metadata only.
