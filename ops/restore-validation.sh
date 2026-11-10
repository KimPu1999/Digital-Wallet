#!/usr/bin/env sh
set -eu

: "${BACKUP_FILE:?Set BACKUP_FILE to an approved backup artifact}"
: "${TARGET_DATABASE_URL:?Set TARGET_DATABASE_URL to an isolated non-production database}"
if [ "${CONFIRM_NON_PRODUCTION:-}" != "YES" ]; then
  echo "Refusing restore validation: set CONFIRM_NON_PRODUCTION=YES for an isolated target." >&2
  exit 2
fi
case "$TARGET_DATABASE_URL" in
  *production*|*prod*) echo "Refusing a target that appears to be production." >&2; exit 2;;
esac
pg_restore --list "$BACKUP_FILE" >/dev/null
echo "Backup archive is readable. Restore into the isolated target using the approved operator procedure, then run migrations/status and ledger reconciliation."
