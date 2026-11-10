<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('merchant_payment_links', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->string('public_token_hash', 128)->unique();
            $table->unsignedBigInteger('amount_minor')->nullable();
            $table->char('currency_code', 3);
            $table->string('description', 255)->nullable();
            $table->string('status', 24)->default('active');
            $table->timestampTz('expires_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->boolean('single_use')->default(false);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['merchant_id', 'status', 'created_at']);
            $table->index(['expires_at', 'status']);
        });

        Schema::create('checkout_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('payment_link_id')->nullable()->constrained('merchant_payment_links')->nullOnDelete();
            $table->foreignUuid('payment_intent_id')->nullable()->unique()->constrained('payment_intents')->nullOnDelete();
            $table->string('public_token_hash', 128)->unique();
            $table->string('order_reference', 120);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency_code', 3);
            $table->string('status', 24)->default('open');
            $table->string('payment_method', 32)->nullable();
            $table->string('customer_email', 255)->nullable();
            $table->timestampTz('expires_at');
            $table->text('success_url')->nullable();
            $table->text('cancel_url')->nullable();
            $table->json('allowed_payment_methods')->nullable();
            $table->json('branding_snapshot')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['merchant_id', 'status', 'created_at']);
            $table->index(['expires_at', 'status']);
        });

        Schema::create('merchant_qr_codes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('checkout_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('qr_reference', 120)->unique();
            $table->string('token_hash', 128)->unique();
            $table->string('qr_type', 16);
            $table->unsignedBigInteger('amount_minor')->nullable();
            $table->char('currency_code', 3)->nullable();
            $table->string('status', 24)->default('active');
            $table->timestampTz('expires_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestampsTz();
            $table->index(['merchant_id', 'qr_type', 'status', 'created_at']);
        });

        Schema::create('refund_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('payment_intent_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('ledger_transaction_id')->nullable()->unique()->constrained('ledger_transactions')->nullOnDelete();
            $table->string('idempotency_key', 255);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency_code', 3);
            $table->string('reason', 255);
            $table->string('status', 24)->default('requested');
            $table->string('provider_reference', 160)->nullable();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->unique(['merchant_id', 'idempotency_key']);
            $table->index(['merchant_id', 'status', 'created_at']);
            $table->index(['payment_intent_id', 'status']);
        });

        Schema::create('merchant_settlements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('settlement_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('settlement_reference', 160)->unique();
            $table->char('currency_code', 3);
            $table->unsignedBigInteger('gross_amount_minor')->default(0);
            $table->unsignedBigInteger('fee_amount_minor')->default(0);
            $table->unsignedBigInteger('refund_amount_minor')->default(0);
            $table->bigInteger('net_amount_minor')->default(0);
            $table->string('status', 24)->default('projected');
            $table->timestampTz('period_start');
            $table->timestampTz('period_end');
            $table->timestampTz('settled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['merchant_id', 'currency_code', 'status', 'period_end']);
        });

        Schema::create('merchant_api_keys', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->string('name', 100);
            $table->string('key_prefix', 24);
            $table->string('key_hash', 128)->unique();
            $table->json('scopes');
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();
            $table->index(['merchant_id', 'revoked_at', 'expires_at']);
        });

        Schema::create('merchant_webhook_endpoints', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->text('url');
            $table->text('signing_secret_encrypted');
            $table->json('events');
            $table->string('status', 24)->default('active');
            $table->timestampTz('last_delivery_at')->nullable();
            $table->timestampsTz();
            $table->index(['merchant_id', 'status']);
        });

        Schema::create('merchant_webhook_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_webhook_endpoint_id')->constrained()->cascadeOnDelete();
            $table->string('event_id', 160);
            $table->string('event_type', 80);
            $table->text('payload_encrypted');
            $table->string('status', 24)->default('queued');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->timestampTz('next_attempt_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->string('error_message', 255)->nullable();
            $table->timestampsTz();
            $table->unique(['merchant_webhook_endpoint_id', 'event_id']);
            $table->index(['status', 'next_attempt_at']);
        });

        Schema::create('merchant_checkout_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('merchant_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('branding')->nullable();
            $table->json('allowed_currencies')->nullable();
            $table->json('allowed_payment_methods')->nullable();
            $table->json('allowed_redirect_hosts')->nullable();
            $table->text('default_success_url')->nullable();
            $table->text('default_cancel_url')->nullable();
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE merchant_payment_links ADD CONSTRAINT merchant_payment_links_status_check CHECK (status IN ('active','inactive','expired'))");
        DB::statement("ALTER TABLE checkout_sessions ADD CONSTRAINT checkout_sessions_status_check CHECK (status IN ('open','processing','paid','failed','expired','cancelled'))");
        DB::statement("ALTER TABLE merchant_qr_codes ADD CONSTRAINT merchant_qr_codes_type_check CHECK (qr_type IN ('static','dynamic'))");
        DB::statement("ALTER TABLE merchant_qr_codes ADD CONSTRAINT merchant_qr_codes_status_check CHECK (status IN ('active','used','expired','disabled'))");
        DB::statement("ALTER TABLE refund_requests ADD CONSTRAINT refund_requests_status_check CHECK (status IN ('requested','approved','succeeded','failed','rejected','cancelled'))");
        DB::statement("ALTER TABLE merchant_settlements ADD CONSTRAINT merchant_settlements_status_check CHECK (status IN ('projected','pending','settled','exception'))");
    }

    public function down(): void
    {
        foreach (['merchant_checkout_settings', 'merchant_webhook_deliveries', 'merchant_webhook_endpoints', 'merchant_api_keys', 'merchant_settlements', 'refund_requests', 'merchant_qr_codes', 'checkout_sessions', 'merchant_payment_links'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
