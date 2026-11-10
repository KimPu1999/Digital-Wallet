<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fx_rate_providers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 48)->unique();
            $table->string('name', 120);
            $table->string('provider_type', 24)->default('sandbox');
            $table->string('status', 24)->default('active');
            $table->unsignedInteger('timeout_ms')->default(1000);
            $table->unsignedInteger('stale_after_seconds')->default(120);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
        });
        Schema::create('fx_pairs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->char('base_currency_code', 3);
            $table->char('quote_currency_code', 3);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('provider_available')->default(true);
            $table->unsignedBigInteger('min_amount_minor')->default(1);
            $table->unsignedBigInteger('max_amount_minor')->nullable();
            $table->unsignedInteger('quote_expiry_seconds')->default(60);
            $table->json('country_eligibility')->nullable();
            $table->json('risk_restrictions')->nullable();
            $table->timestampsTz();
            $table->unique(['base_currency_code', 'quote_currency_code']);
            $table->index(['is_enabled', 'base_currency_code', 'quote_currency_code']);
        });
        Schema::create('fx_rate_snapshots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('fx_rate_providers')->restrictOnDelete();
            $table->foreignUuid('pair_id')->constrained('fx_pairs')->restrictOnDelete();
            $table->decimal('rate', 36, 18);
            $table->decimal('inverse_rate', 36, 18)->nullable();
            $table->timestampTz('observed_at');
            $table->timestampTz('expires_at');
            $table->string('provider_reference', 180)->nullable();
            $table->string('status', 24)->default('fresh');
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['pair_id', 'observed_at']);
        });
        Schema::create('fx_fee_rules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pair_id')->nullable()->constrained('fx_pairs')->nullOnDelete();
            $table->unsignedBigInteger('fixed_fee_minor')->default(0);
            $table->unsignedInteger('percentage_bps')->default(0);
            $table->unsignedInteger('provider_spread_bps')->default(0);
            $table->unsignedInteger('platform_markup_bps')->default(0);
            $table->unsignedBigInteger('minimum_fee_minor')->default(0);
            $table->unsignedBigInteger('maximum_fee_minor')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestampsTz();
            $table->index(['pair_id', 'is_enabled']);
        });
        Schema::create('fx_quotes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('quote_reference', 80)->unique();
            $table->foreignUuid('user_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('source_wallet_currency_account_id')->constrained('wallet_currency_accounts')->restrictOnDelete();
            $table->foreignUuid('target_wallet_currency_account_id')->constrained('wallet_currency_accounts')->restrictOnDelete();
            $table->foreignUuid('pair_id')->constrained('fx_pairs')->restrictOnDelete();
            $table->foreignUuid('rate_snapshot_id')->constrained('fx_rate_snapshots')->restrictOnDelete();
            $table->char('source_currency_code', 3);
            $table->char('target_currency_code', 3);
            $table->unsignedBigInteger('source_amount_minor');
            $table->decimal('mid_market_rate', 36, 18);
            $table->decimal('exchange_rate', 36, 18);
            $table->string('rate_direction', 24)->default('base_to_quote');
            $table->unsignedInteger('provider_spread_bps')->default(0);
            $table->unsignedInteger('platform_markup_bps')->default(0);
            $table->unsignedBigInteger('platform_fee_minor')->default(0);
            $table->string('provider_reference', 180)->nullable();
            $table->unsignedBigInteger('estimated_target_amount_minor');
            $table->string('rounding_policy', 40)->default('floor_target_minor');
            $table->string('status', 24)->default('quoted');
            $table->boolean('sandbox_only')->default(true);
            $table->timestampTz('expires_at');
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'created_at']);
            $table->index(['source_currency_code', 'target_currency_code', 'status']);
        });
        Schema::create('fx_conversions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('quote_id')->unique()->constrained('fx_quotes')->restrictOnDelete();
            $table->foreignUuid('user_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('source_wallet_currency_account_id')->constrained('wallet_currency_accounts')->restrictOnDelete();
            $table->foreignUuid('target_wallet_currency_account_id')->constrained('wallet_currency_accounts')->restrictOnDelete();
            $table->char('source_currency_code', 3);
            $table->char('target_currency_code', 3);
            $table->unsignedBigInteger('source_amount_minor');
            $table->unsignedBigInteger('target_amount_minor');
            $table->unsignedBigInteger('fee_minor')->default(0);
            $table->decimal('exchange_rate', 36, 18);
            $table->string('status', 24)->default('processing');
            $table->foreignUuid('ledger_transaction_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignUuid('idempotency_key_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->boolean('sandbox_only')->default(true);
            $table->string('failure_reason', 255)->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
        Schema::create('fx_conversion_legs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversion_id')->constrained('fx_conversions')->cascadeOnDelete();
            $table->foreignUuid('ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->char('currency_code', 3);
            $table->string('leg_type', 24);
            $table->string('side', 8);
            $table->unsignedBigInteger('amount_minor');
            $table->unsignedSmallInteger('sequence');
            $table->timestampsTz();
            $table->unique(['conversion_id', 'sequence']);
        });
        Schema::create('fx_audit_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('quote_id')->nullable()->constrained('fx_quotes')->nullOnDelete();
            $table->foreignUuid('conversion_id')->nullable()->constrained('fx_conversions')->nullOnDelete();
            $table->string('action', 80);
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['action', 'created_at']);
        });
        Schema::create('fx_reconciliation_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversion_id')->nullable()->constrained('fx_conversions')->nullOnDelete();
            $table->string('item_type', 48);
            $table->string('status', 24);
            $table->bigInteger('expected_minor')->nullable();
            $table->bigInteger('actual_minor')->nullable();
            $table->bigInteger('difference_minor')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['item_type', 'status', 'created_at']);
        });

        foreach (['fx_quotes' => "status IN ('quoted','accepted','expired','consumed','rejected')", 'fx_conversions' => "status IN ('processing','completed','failed','expired','rejected','cancelled')", 'fx_conversion_legs' => "side IN ('debit','credit')"] as $table => $check) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_state_check CHECK ({$check})");
        }
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("CREATE OR REPLACE FUNCTION prevent_fx_quote_mutation() RETURNS trigger AS $$ BEGIN RAISE EXCEPTION 'FX quotes are immutable'; END; $$ LANGUAGE plpgsql;");
            DB::statement("CREATE TRIGGER fx_quotes_immutable BEFORE UPDATE OR DELETE ON fx_quotes FOR EACH ROW EXECUTE FUNCTION prevent_fx_quote_mutation();");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS fx_quotes_immutable ON fx_quotes');
            DB::statement('DROP FUNCTION IF EXISTS prevent_fx_quote_mutation()');
        }
        foreach (['fx_reconciliation_items','fx_audit_events','fx_conversion_legs','fx_conversions','fx_quotes','fx_fee_rules','fx_rate_snapshots','fx_pairs','fx_rate_providers'] as $table) Schema::dropIfExists($table);
    }
};
