# Report exports

CSV exports are stored on Laravel's private local disk under a report-run-specific key. Downloads require authenticated platform financial-report permission and reject expired runs.

Fields are quoted and values beginning with `=`, `+`, `-`, or `@` are prefixed with an apostrophe to reduce spreadsheet formula-injection risk. Export records include checksums, row counts, expiration, and audit metadata. Public unrestricted report URLs are not used.

XLSX/PDF generation remains disabled until maintained libraries, queue workers, file limits, and redaction policies are configured.
