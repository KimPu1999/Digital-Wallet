<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { /* Reserved: ledger_accounts, ledger_transactions, ledger_entries, transfers, deposits, withdrawals, idempotency_keys, reconciliation_runs. No financial posting tables are enabled in Phase 1. */ } public function down(): void {} };
