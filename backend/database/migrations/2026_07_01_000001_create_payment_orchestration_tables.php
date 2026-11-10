<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_providers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 48)->unique();
            $table->string('name', 120);
            $table->string('provider_type', 32);
            $table->string('environment', 16)->default('sandbox');
            $table->string('status', 24)->default('disabled');
            $table->boolean('enabled')->default(false);
            $table->json('supported_countries')->nullable();
            $table->json('supported_currencies')->nullable();
            $table->json('supported_payment_methods')->nullable();
            $table->json('supported_operations')->nullable();
            $table->string('health_status', 24)->default('unknown');
            $table->unsignedInteger('timeout_ms')->default(5000);
            $table->json('fee_metadata')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['enabled', 'environment', 'health_status']);
        });

        Schema::create('provider_accounts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('payment_providers')->cascadeOnDelete();
            $table->string('account_key', 100);
            $table->string('environment', 16)->default('sandbox');
            $table->text('encrypted_credentials')->nullable();
            $table->string('status', 24)->default('disabled');
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->unique(['provider_id', 'account_key', 'environment']);
            $table->index(['provider_id', 'status']);
        });

        Schema::create('provider_capabilities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('payment_providers')->cascadeOnDelete();
            $table->string('operation', 32);
            $table->char('country_code', 2)->nullable();
            $table->char('currency_code', 3)->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->boolean('is_supported')->default(true);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['provider_id', 'operation', 'is_supported']);
            $table->index(['currency_code', 'country_code', 'payment_method']);
        });

        Schema::create('provider_routing_rules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('payment_providers')->cascadeOnDelete();
            $table->string('operation', 32);
            $table->char('country_code', 2)->nullable();
            $table->char('currency_code', 3)->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('is_enabled')->default(true);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['operation', 'priority', 'is_enabled']);
        });

        Schema::create('provider_health_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('payment_providers')->cascadeOnDelete();
            $table->string('event_type', 32);
            $table->string('status', 24);
            $table->string('safe_message', 255)->nullable();
            $table->timestampTz('occurred_at');
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['provider_id', 'occurred_at']);
        });

        Schema::create('payment_intents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignUuid('merchant_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignUuid('wallet_currency_account_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('operation', 32);
            $table->string('payment_method', 32);
            $table->char('currency_code', 3);
            $table->unsignedBigInteger('amount_minor');
            $table->unsignedBigInteger('fee_minor')->default(0);
            $table->foreignUuid('provider_id')->nullable()->constrained('payment_providers')->nullOnDelete();
            $table->foreignUuid('provider_account_id')->nullable()->constrained('provider_accounts')->nullOnDelete();
            $table->foreignUuid('idempotency_key_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('intent_reference', 100)->unique();
            $table->string('external_reference', 120)->nullable()->index();
            $table->string('status', 32)->default('created');
            $table->string('failure_reason', 255)->nullable();
            $table->string('settlement_reference', 120)->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->boolean('sandbox_only')->default(true);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'operation', 'status', 'created_at']);
            $table->index(['merchant_id', 'operation', 'status', 'created_at']);
            $table->index(['provider_id', 'status', 'created_at']);
        });

        Schema::create('payment_attempts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_intent_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('provider_id')->constrained('payment_providers')->restrictOnDelete();
            $table->unsignedSmallInteger('attempt_number');
            $table->string('status', 32)->default('created');
            $table->string('provider_reference', 120)->nullable();
            $table->string('failure_reason', 255)->nullable();
            $table->timestampTz('started_at');
            $table->timestampTz('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->unique(['payment_intent_id', 'attempt_number']);
            $table->index(['provider_id', 'status', 'created_at']);
        });

        Schema::create('provider_transactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_attempt_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('payment_intent_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('provider_id')->constrained('payment_providers')->restrictOnDelete();
            $table->string('provider_event_id', 160)->nullable();
            $table->string('external_reference', 160)->nullable();
            $table->string('status', 32);
            $table->char('currency_code', 3);
            $table->unsignedBigInteger('amount_minor');
            $table->unsignedBigInteger('fee_minor')->default(0);
            $table->timestampTz('observed_at');
            $table->json('raw_payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->unique(['provider_id', 'provider_event_id']);
            $table->unique(['provider_id', 'external_reference']);
            $table->index(['payment_intent_id', 'status']);
        });

        Schema::create('payment_webhook_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('payment_providers')->restrictOnDelete();
            $table->string('event_id', 160);
            $table->string('event_type', 80);
            $table->boolean('signature_valid')->default(false);
            $table->string('status', 24)->default('received');
            $table->text('raw_body_encrypted');
            $table->timestampTz('received_at');
            $table->timestampTz('event_occurred_at')->nullable();
            $table->timestampTz('processed_at')->nullable();
            $table->string('error_message', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->unique(['provider_id', 'event_id']);
            $table->index(['status', 'received_at']);
        });

        Schema::create('settlement_batches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('payment_providers')->restrictOnDelete();
            $table->string('settlement_reference', 160);
            $table->char('currency_code', 3);
            $table->string('status', 24)->default('open');
            $table->unsignedBigInteger('total_amount_minor')->default(0);
            $table->unsignedBigInteger('total_fee_minor')->default(0);
            $table->timestampTz('expected_at')->nullable();
            $table->timestampTz('settled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->unique(['provider_id', 'settlement_reference']);
            $table->index(['currency_code', 'status', 'created_at']);
        });

        Schema::create('settlement_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('settlement_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('payment_intent_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('provider_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('ledger_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->char('currency_code', 3);
            $table->unsignedBigInteger('amount_minor');
            $table->unsignedBigInteger('fee_minor')->default(0);
            $table->string('status', 24)->default('unmatched');
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->unique(['settlement_batch_id', 'payment_intent_id']);
            $table->index(['status', 'currency_code']);
        });

        Schema::create('payment_reconciliation_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_intent_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('provider_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('settlement_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('exception_type', 48);
            $table->string('status', 24)->default('open');
            $table->bigInteger('expected_minor')->nullable();
            $table->bigInteger('actual_minor')->nullable();
            $table->bigInteger('difference_minor')->nullable();
            $table->string('reason', 255);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'exception_type', 'created_at']);
        });

        DB::statement("ALTER TABLE payment_providers ADD CONSTRAINT payment_providers_environment_check CHECK (environment IN ('sandbox','production'))");
        DB::statement("ALTER TABLE payment_intents ADD CONSTRAINT payment_intents_operation_check CHECK (operation IN ('deposit','withdrawal','merchant_payment'))");
        DB::statement("ALTER TABLE payment_intents ADD CONSTRAINT payment_intents_amount_check CHECK (amount_minor > 0)");
        DB::statement("ALTER TABLE payment_intents ADD CONSTRAINT payment_intents_sandbox_check CHECK (sandbox_only = TRUE)");
        DB::statement("ALTER TABLE payment_webhook_events ADD CONSTRAINT payment_webhook_events_status_check CHECK (status IN ('received','queued','processed','ignored','failed','rejected'))");

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("CREATE OR REPLACE FUNCTION prevent_payment_record_mutation() RETURNS trigger AS $$ BEGIN RAISE EXCEPTION 'Immutable payment record: %', TG_TABLE_NAME; END; $$ LANGUAGE plpgsql;");
            DB::statement("CREATE TRIGGER provider_transactions_immutable BEFORE UPDATE OR DELETE ON provider_transactions FOR EACH ROW EXECUTE FUNCTION prevent_payment_record_mutation();");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS provider_transactions_immutable ON provider_transactions');
            DB::statement('DROP FUNCTION IF EXISTS prevent_payment_record_mutation()');
        }
        foreach (['payment_reconciliation_items', 'settlement_items', 'settlement_batches', 'payment_webhook_events', 'provider_transactions', 'payment_attempts', 'payment_intents', 'provider_health_events', 'provider_routing_rules', 'provider_capabilities', 'provider_accounts', 'payment_providers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
