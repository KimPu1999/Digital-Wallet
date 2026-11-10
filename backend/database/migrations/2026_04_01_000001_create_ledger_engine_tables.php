<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ledger_accounts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallet_currency_account_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->char('currency_code', 3);
            $table->string('code', 80)->unique();
            $table->string('name', 160);
            $table->string('category', 24);
            $table->string('normal_side', 8);
            $table->string('status', 24)->default('active');
            $table->boolean('restricted')->default(false);
            $table->timestamps();
            $table->index(['currency_code', 'category', 'status']);
        });

        Schema::create('idempotency_keys', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('scope', 120);
            $table->string('key', 255);
            $table->char('request_fingerprint', 64);
            $table->string('status', 24)->default('reserved');
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->json('response_body')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampsTz();
            $table->unique(['scope', 'key']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('ledger_transactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('reference', 120)->unique();
            $table->string('transaction_type', 48);
            $table->string('status', 24)->default('pending');
            $table->foreignUuid('idempotency_key_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('description', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('posted_at')->nullable();
            $table->timestampsTz();
            $table->index(['transaction_type', 'status', 'created_at']);
        });

        Schema::create('ledger_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('ledger_transaction_id')->constrained('ledger_transactions')->restrictOnDelete();
            $table->foreignUuid('ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->char('currency_code', 3);
            $table->string('side', 8);
            $table->unsignedBigInteger('amount_minor');
            $table->unsignedInteger('sequence');
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['ledger_transaction_id', 'sequence']);
            $table->index(['ledger_account_id', 'currency_code', 'created_at']);
            $table->index(['ledger_transaction_id', 'currency_code']);
        });

        Schema::create('ledger_reversals', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('original_transaction_id')->unique()->constrained('ledger_transactions')->restrictOnDelete();
            $table->foreignUuid('reversal_transaction_id')->unique()->constrained('ledger_transactions')->restrictOnDelete();
            $table->foreignUuid('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('reason', 255);
            $table->timestampsTz();
        });

        Schema::create('balance_projections', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallet_currency_account_id')->unique()->constrained()->cascadeOnDelete();
            $table->char('currency_code', 3);
            $table->bigInteger('posted_minor')->default(0);
            $table->unsignedBigInteger('held_minor')->default(0);
            $table->unsignedBigInteger('version')->default(0);
            $table->timestampTz('last_posted_at')->nullable();
            $table->timestampsTz();
            $table->index(['currency_code', 'updated_at']);
        });

        Schema::create('wallet_holds', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallet_currency_account_id')->constrained()->restrictOnDelete();
            $table->char('currency_code', 3);
            $table->unsignedBigInteger('amount_minor');
            $table->string('status', 16)->default('active');
            $table->string('reference', 120)->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('idempotency_key_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->timestampTz('captured_at')->nullable();
            $table->timestampTz('released_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['wallet_currency_account_id', 'status', 'expires_at']);
            $table->unique(['wallet_currency_account_id', 'reference']);
        });

        Schema::create('reconciliation_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('run_type', 48);
            $table->string('status', 24)->default('running');
            $table->foreignUuid('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('summary')->nullable();
            $table->timestampTz('started_at');
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
            $table->index(['run_type', 'status', 'started_at']);
        });

        Schema::create('reconciliation_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('reconciliation_run_id')->constrained()->cascadeOnDelete();
            $table->string('item_type', 48);
            $table->string('entity_type', 80);
            $table->uuid('entity_id');
            $table->string('status', 24);
            $table->bigInteger('expected_minor')->nullable();
            $table->bigInteger('actual_minor')->nullable();
            $table->bigInteger('difference_minor')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['reconciliation_run_id', 'status']);
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('financial_audit_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('action', 80);
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entity_type', 80)->nullable();
            $table->uuid('entity_id')->nullable();
            $table->string('request_id', 120)->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['action', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('outbox_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('event_type', 100);
            $table->string('aggregate_type', 80);
            $table->uuid('aggregate_id');
            $table->json('payload');
            $table->string('status', 24)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('available_at')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'available_at']);
            $table->index(['aggregate_type', 'aggregate_id']);
        });

        DB::statement("ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_accounts_category_check CHECK (category IN ('asset','liability','equity','revenue','expense'))");
        DB::statement("ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_accounts_normal_side_check CHECK (normal_side IN ('debit','credit'))");
        DB::statement("ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entries_side_check CHECK (side IN ('debit','credit'))");
        DB::statement("ALTER TABLE ledger_transactions ADD CONSTRAINT ledger_transactions_status_check CHECK (status IN ('pending','posted','failed','reversed'))");
        DB::statement("ALTER TABLE wallet_holds ADD CONSTRAINT wallet_holds_status_check CHECK (status IN ('active','captured','released','expired'))");

        if (DB::connection()->getDriverName() === 'pgsql') {
            foreach (['ledger_entries', 'ledger_reversals', 'financial_audit_events'] as $table) {
                DB::statement("CREATE OR REPLACE FUNCTION prevent_{$table}_mutation() RETURNS trigger AS $$ BEGIN RAISE EXCEPTION 'Immutable financial record: {$table}'; END; $$ LANGUAGE plpgsql;");
                DB::statement("CREATE TRIGGER {$table}_immutable BEFORE UPDATE OR DELETE ON {$table} FOR EACH ROW EXECUTE FUNCTION prevent_{$table}_mutation();");
            }
            DB::statement("CREATE OR REPLACE FUNCTION enforce_ledger_transaction_balance() RETURNS trigger AS $$ DECLARE mismatch_count integer; BEGIN IF NEW.status = 'posted' AND (TG_OP = 'INSERT' OR (TG_OP = 'UPDATE' AND OLD.status IS DISTINCT FROM NEW.status)) THEN SELECT COUNT(*) INTO mismatch_count FROM (SELECT currency_code FROM ledger_entries WHERE ledger_transaction_id = NEW.id GROUP BY currency_code HAVING COALESCE(SUM(amount_minor) FILTER (WHERE side = 'debit'), 0) <> COALESCE(SUM(amount_minor) FILTER (WHERE side = 'credit'), 0)) balances; IF mismatch_count > 0 THEN RAISE EXCEPTION 'Ledger transaction is not balanced by currency'; END IF; END IF; RETURN NEW; END; $$ LANGUAGE plpgsql;");
            DB::statement("CREATE CONSTRAINT TRIGGER ledger_transaction_balance AFTER INSERT OR UPDATE OF status ON ledger_transactions DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION enforce_ledger_transaction_balance();");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS ledger_transaction_balance ON ledger_transactions');
            DB::statement('DROP FUNCTION IF EXISTS enforce_ledger_transaction_balance()');
        }
        if (DB::connection()->getDriverName() === 'pgsql') {
            foreach (['ledger_entries', 'ledger_reversals', 'financial_audit_events'] as $table) {
                DB::statement("DROP TRIGGER IF EXISTS {$table}_immutable ON {$table}");
                DB::statement("DROP FUNCTION IF EXISTS prevent_{$table}_mutation()");
            }
        }
        foreach (['outbox_events', 'financial_audit_events', 'reconciliation_items', 'reconciliation_runs', 'wallet_holds', 'balance_projections', 'ledger_reversals', 'ledger_entries', 'ledger_transactions', 'idempotency_keys', 'ledger_accounts'] as $table) Schema::dropIfExists($table);
    }
};
