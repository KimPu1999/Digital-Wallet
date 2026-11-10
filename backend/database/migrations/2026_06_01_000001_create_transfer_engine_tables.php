<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('merchant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('owner_type', 16)->default('customer');
            $table->string('display_name', 120);
            $table->string('beneficiary_type', 32);
            $table->char('country_code', 2)->nullable();
            $table->string('status', 24)->default('active');
            $table->string('verification_status', 24)->default('unverified');
            $table->string('risk_status', 24)->default('not_screened');
            $table->string('search_name', 160);
            $table->string('details_fingerprint', 64);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['merchant_id', 'status', 'created_at']);
            $table->unique(['user_id', 'details_fingerprint']);
            $table->unique(['merchant_id', 'details_fingerprint']);
        });
        Schema::create('beneficiary_accounts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->string('account_type', 32);
            $table->char('currency_code', 3)->nullable();
            $table->text('account_details_encrypted');
            $table->string('account_last4', 4)->nullable();
            $table->string('status', 24)->default('active');
            $table->timestampsTz();
            $table->index(['beneficiary_id', 'status']);
        });
        Schema::create('transfer_quotes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('quote_reference', 80)->unique();
            $table->foreignUuid('user_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('source_wallet_currency_account_id')->constrained('wallet_currency_accounts')->restrictOnDelete();
            $table->foreignUuid('beneficiary_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('beneficiary_account_id')->constrained()->restrictOnDelete();
            $table->char('source_currency_code', 3);
            $table->char('destination_currency_code', 3);
            $table->unsignedBigInteger('source_amount_minor');
            $table->unsignedBigInteger('recipient_amount_minor');
            $table->unsignedBigInteger('provider_fee_minor')->default(0);
            $table->unsignedBigInteger('platform_fee_minor')->default(0);
            $table->decimal('exchange_rate', 36, 18)->default(1);
            $table->string('fx_quote_reference', 120)->nullable();
            $table->string('corridor_status', 24)->default('sandbox_allowed');
            $table->string('status', 24)->default('quoted');
            $table->boolean('sandbox_only')->default(true);
            $table->timestampTz('expires_at');
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['source_currency_code', 'destination_currency_code', 'status']);
        });
        Schema::create('transfers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('transfer_reference', 80)->unique();
            $table->foreignUuid('user_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('source_wallet_currency_account_id')->constrained('wallet_currency_accounts')->restrictOnDelete();
            $table->foreignUuid('beneficiary_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('beneficiary_account_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('quote_id')->nullable()->constrained('transfer_quotes')->restrictOnDelete();
            $table->char('source_currency_code', 3);
            $table->char('destination_currency_code', 3);
            $table->unsignedBigInteger('source_amount_minor');
            $table->unsignedBigInteger('recipient_amount_minor');
            $table->unsignedBigInteger('provider_fee_minor')->default(0);
            $table->unsignedBigInteger('platform_fee_minor')->default(0);
            $table->decimal('exchange_rate', 36, 18)->default(1);
            $table->string('transfer_type', 32)->default('international');
            $table->string('status', 32)->default('draft');
            $table->string('failure_reason', 255)->nullable();
            $table->string('sandbox_provider_reference', 120)->nullable();
            $table->foreignUuid('hold_id')->nullable()->unique()->constrained('wallet_holds')->restrictOnDelete();
            $table->foreignUuid('ledger_transaction_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignUuid('idempotency_key_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->boolean('sandbox_only')->default(true);
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['beneficiary_id', 'status']);
        });
        Schema::create('transfer_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('transfer_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->string('event_type', 48);
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['transfer_id', 'created_at']);
        });
        Schema::create('transfer_screenings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('transfer_id')->constrained()->cascadeOnDelete();
            $table->string('screening_type', 32);
            $table->string('provider', 48)->default('sandbox-mock');
            $table->string('status', 24)->default('pending');
            $table->string('decision', 24)->nullable();
            $table->string('reference', 120)->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->unique(['transfer_id', 'screening_type']);
        });
        Schema::create('transfer_attempts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('transfer_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_number');
            $table->string('provider', 48)->default('sandbox-mock');
            $table->string('status', 24)->default('accepted');
            $table->string('provider_reference', 120)->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestampTz('started_at');
            $table->timestampTz('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->unique(['transfer_id', 'attempt_number']);
        });
        Schema::create('transfer_limits', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('scope_type', 24);
            $table->string('scope_key', 80);
            $table->char('currency_code', 3)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('kyc_tier', 32)->nullable();
            $table->unsignedBigInteger('per_transaction_minor')->nullable();
            $table->unsignedBigInteger('daily_minor')->nullable();
            $table->unsignedBigInteger('monthly_minor')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['scope_type', 'scope_key', 'currency_code', 'is_enabled']);
        });
        Schema::create('transfer_limit_usages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('limit_id')->constrained('transfer_limits')->cascadeOnDelete();
            $table->char('currency_code', 3);
            $table->date('usage_date');
            $table->unsignedBigInteger('daily_minor')->default(0);
            $table->unsignedBigInteger('monthly_minor')->default(0);
            $table->unsignedBigInteger('version')->default(0);
            $table->timestampsTz();
            $table->unique(['user_id', 'limit_id', 'currency_code', 'usage_date']);
        });
        Schema::create('transfer_receipts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('transfer_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('receipt_reference', 100)->unique();
            $table->string('receipt_hash', 64);
            $table->json('snapshot');
            $table->timestampTz('issued_at');
            $table->timestampsTz();
        });
        Schema::create('transfer_step_up_challenges', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('transfer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('challenge_hash', 64);
            $table->string('method', 24)->default('mfa');
            $table->string('status', 24)->default('pending');
            $table->timestampTz('expires_at');
            $table->timestampTz('consumed_at')->nullable();
            $table->string('session_fingerprint', 128)->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'status', 'expires_at']);
        });

        foreach ([
            'beneficiaries' => "owner_type IN ('customer','merchant') AND ((owner_type = 'customer' AND user_id IS NOT NULL AND merchant_id IS NULL) OR (owner_type = 'merchant' AND merchant_id IS NOT NULL))",
            'beneficiary_accounts' => "account_type IN ('internal_wallet','bank_account','payout_provider')",
            'transfers' => "status IN ('draft','quoted','awaiting_confirmation','pending_compliance','processing','completed','rejected','failed','cancelled','expired','returned')",
            'transfer_screenings' => "status IN ('pending','approved','review','rejected','failed')",
            'transfer_attempts' => "status IN ('accepted','processing','completed','rejected','failed','timed_out','returned')",
            'transfer_step_up_challenges' => "status IN ('pending','consumed','expired','rejected')",
        ] as $table => $constraint) DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_state_check CHECK ({$constraint})");
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("CREATE OR REPLACE FUNCTION prevent_transfer_receipt_mutation() RETURNS trigger AS $$ BEGIN RAISE EXCEPTION 'Transfer receipt is immutable'; END; $$ LANGUAGE plpgsql;");
            DB::statement("CREATE TRIGGER transfer_receipts_immutable BEFORE UPDATE OR DELETE ON transfer_receipts FOR EACH ROW EXECUTE FUNCTION prevent_transfer_receipt_mutation();");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS transfer_receipts_immutable ON transfer_receipts');
            DB::statement('DROP FUNCTION IF EXISTS prevent_transfer_receipt_mutation()');
        }
        foreach (['transfer_step_up_challenges','transfer_receipts','transfer_limit_usages','transfer_limits','transfer_attempts','transfer_screenings','transfer_events','transfers','transfer_quotes','beneficiary_accounts','beneficiaries'] as $table) Schema::dropIfExists($table);
    }
};
