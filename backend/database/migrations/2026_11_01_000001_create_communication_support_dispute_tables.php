<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->uuidMorphs('notifiable');
            $table->text('data');
            $table->timestampTz('read_at')->nullable();
            $table->timestampsTz();
            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
        });

        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('transaction_email')->default(true);
            $table->boolean('transaction_sms')->default(false);
            $table->boolean('transaction_push')->default(true);
            $table->boolean('support_email')->default(true);
            $table->boolean('support_push')->default(true);
            $table->boolean('product_email')->default(false);
            $table->boolean('marketing_email')->default(false);
            $table->boolean('marketing_sms')->default(false);
            $table->boolean('marketing_push')->default(false);
            $table->timestampsTz();
        });

        Schema::create('notification_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('template_key', 120);
            $table->string('locale', 8);
            $table->unsignedInteger('version');
            $table->string('subject', 255)->nullable();
            $table->text('email_body')->nullable();
            $table->text('sms_body')->nullable();
            $table->string('push_title', 255)->nullable();
            $table->text('push_body')->nullable();
            $table->text('in_app_body')->nullable();
            $table->boolean('mandatory')->default(false);
            $table->string('status', 24)->default('active');
            $table->timestampsTz();
            $table->unique(['template_key', 'locale', 'version']);
            $table->index(['template_key', 'locale', 'status']);
        });

        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('notification_id')->nullable()->constrained('notifications')->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 24);
            $table->string('status', 24)->default('queued');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampTz('next_attempt_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->string('provider_reference', 180)->nullable();
            $table->string('failure_reason', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'channel', 'status', 'created_at']);
            $table->index(['status', 'next_attempt_at']);
        });

        Schema::create('notification_event_receipts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('event_key', 180)->unique();
            $table->string('event_type', 120);
            $table->foreignUuid('user_id')->nullable()->nullOnDelete();
            $table->foreignUuid('notification_id')->nullable()->nullOnDelete();
            $table->timestampTz('processed_at');
            $table->json('metadata')->nullable();
            $table->index(['event_type', 'processed_at']);
        });

        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->nullOnDelete();
            $table->foreignUuid('merchant_id')->nullable()->nullOnDelete();
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('assigned_to')->nullable()->nullOnDelete();
            $table->string('ticket_reference', 40)->unique();
            $table->string('category', 48);
            $table->string('subject', 180);
            $table->string('priority', 24)->default('normal');
            $table->string('status', 32)->default('open');
            $table->text('resolution_summary')->nullable();
            $table->timestampTz('first_response_at')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampTz('closed_at')->nullable();
            $table->timestampTz('sla_due_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['merchant_id', 'status', 'created_at']);
            $table->index(['assigned_to', 'status', 'priority']);
        });

        Schema::create('support_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignUuid('sender_id')->constrained('users')->restrictOnDelete();
            $table->string('sender_type', 24)->default('customer');
            $table->string('visibility', 24)->default('customer');
            $table->text('body');
            $table->string('client_message_key', 180)->nullable();
            $table->timestampsTz();
            $table->unique(['support_ticket_id', 'client_message_key']);
            $table->index(['support_ticket_id', 'visibility', 'created_at']);
        });

        Schema::create('support_attachments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignUuid('support_message_id')->nullable()->constrained('support_messages')->nullOnDelete();
            $table->foreignUuid('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('storage_key', 255);
            $table->string('original_name', 180);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->string('scan_status', 24)->default('pending');
            $table->string('checksum', 128)->nullable();
            $table->timestampsTz();
            $table->index(['support_ticket_id', 'scan_status']);
        });

        Schema::create('support_ticket_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignUuid('actor_id')->nullable()->nullOnDelete();
            $table->string('event_type', 64);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');
            $table->index(['support_ticket_id', 'created_at']);
        });

        Schema::create('support_assignments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignUuid('agent_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('assigned_by')->nullable()->nullOnDelete();
            $table->timestampTz('assigned_at');
            $table->timestampTz('unassigned_at')->nullable();
            $table->index(['agent_id', 'unassigned_at']);
        });

        Schema::create('support_sla_policies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('policy_key', 80);
            $table->unsignedInteger('version');
            $table->string('priority', 24);
            $table->unsignedInteger('response_minutes');
            $table->unsignedInteger('resolution_minutes');
            $table->string('status', 24)->default('active');
            $table->json('business_hours')->nullable();
            $table->timestampsTz();
            $table->unique(['policy_key', 'version', 'priority']);
        });

        Schema::create('dispute_cases', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->nullOnDelete();
            $table->foreignUuid('merchant_id')->nullable()->nullOnDelete();
            $table->foreignUuid('support_ticket_id')->nullable()->unique()->nullOnDelete();
            $table->foreignUuid('payment_intent_id')->nullable()->nullOnDelete();
            $table->foreignUuid('opened_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('assigned_to')->nullable()->nullOnDelete();
            $table->string('dispute_reference', 40)->unique();
            $table->string('classification', 64);
            $table->string('status', 32)->default('open');
            $table->string('priority', 24)->default('normal');
            $table->string('reason', 255);
            $table->text('resolution_summary')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['merchant_id', 'status', 'created_at']);
        });

        Schema::create('dispute_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('dispute_case_id')->constrained('dispute_cases')->cascadeOnDelete();
            $table->foreignUuid('actor_id')->nullable()->nullOnDelete();
            $table->string('event_type', 64);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');
            $table->index(['dispute_case_id', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['dispute_events', 'dispute_cases', 'support_sla_policies', 'support_assignments', 'support_ticket_events', 'support_attachments', 'support_messages', 'support_tickets', 'notification_event_receipts', 'notification_deliveries', 'notification_templates', 'notification_preferences', 'notifications'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
