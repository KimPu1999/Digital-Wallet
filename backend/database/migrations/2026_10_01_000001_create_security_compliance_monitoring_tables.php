<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_risk_profiles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('classification', 24)->default('unknown');
            $table->timestampTz('assessed_at')->nullable();
            $table->timestampTz('review_due_at')->nullable();
            $table->string('screening_status', 32)->default('not_screened');
            $table->json('monitoring_flags')->nullable();
            $table->json('assessment_references')->nullable();
            $table->foreignUuid('last_assessed_by')->nullable()->nullOnDelete();
            $table->timestampsTz();
            $table->index(['classification', 'review_due_at']);
        });

        Schema::create('risk_rules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('rule_key', 120)->unique();
            $table->string('name', 180);
            $table->string('category', 32);
            $table->string('default_decision', 24)->default('review');
            $table->boolean('enabled')->default(false);
            $table->text('description')->nullable();
            $table->timestampsTz();
            $table->index(['category', 'enabled']);
        });

        Schema::create('risk_rule_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('risk_rule_id')->constrained('risk_rules')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('configuration');
            $table->string('status', 24)->default('draft');
            $table->foreignUuid('published_by')->nullable()->nullOnDelete();
            $table->timestampTz('published_at')->nullable();
            $table->timestampsTz();
            $table->unique(['risk_rule_id', 'version']);
            $table->index(['risk_rule_id', 'status']);
        });

        Schema::create('risk_evaluations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('subject_type', 160);
            $table->uuid('subject_id');
            $table->foreignUuid('risk_rule_version_id')->nullable()->nullOnDelete();
            $table->string('decision', 24);
            $table->string('evaluation_key', 180)->unique();
            $table->json('evidence')->nullable();
            $table->string('failure_reason', 255)->nullable();
            $table->timestampsTz();
            $table->index(['subject_type', 'subject_id', 'created_at']);
            $table->index(['decision', 'created_at']);
        });

        Schema::create('risk_alerts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('risk_evaluation_id')->nullable()->nullOnDelete();
            $table->string('subject_type', 160);
            $table->uuid('subject_id');
            $table->string('category', 32);
            $table->string('severity', 24)->default('medium');
            $table->string('status', 24)->default('open');
            $table->string('deduplication_key', 180)->unique();
            $table->string('reason', 255);
            $table->json('evidence')->nullable();
            $table->foreignUuid('assigned_to')->nullable()->nullOnDelete();
            $table->timestampsTz();
            $table->index(['status', 'severity', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('screening_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('subject_type', 80);
            $table->uuid('subject_id');
            $table->string('provider', 80);
            $table->string('status', 32)->default('pending');
            $table->string('provider_reference', 180)->nullable();
            $table->string('request_key', 180)->unique();
            $table->timestampTz('requested_at');
            $table->timestampTz('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['subject_type', 'subject_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('screening_matches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('screening_request_id')->constrained('screening_requests')->cascadeOnDelete();
            $table->string('status', 32)->default('potential_match');
            $table->string('list_source', 120)->nullable();
            $table->string('match_reference', 180)->nullable();
            $table->decimal('match_score', 6, 3)->nullable();
            $table->json('subject_facts')->nullable();
            $table->json('review_metadata')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->nullOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'created_at']);
        });

        Schema::create('compliance_cases', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('subject_type', 160);
            $table->uuid('subject_id');
            $table->foreignUuid('opened_by')->nullable()->nullOnDelete();
            $table->foreignUuid('assigned_to')->nullable()->nullOnDelete();
            $table->string('status', 32)->default('open');
            $table->string('priority', 24)->default('medium');
            $table->string('case_type', 48)->default('monitoring');
            $table->string('reason', 255);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'priority', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('case_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('compliance_case_id')->constrained('compliance_cases')->cascadeOnDelete();
            $table->foreignUuid('actor_id')->nullable()->nullOnDelete();
            $table->string('event_type', 64);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');
            $table->index(['compliance_case_id', 'created_at']);
        });

        Schema::create('case_evidence', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('compliance_case_id')->constrained('compliance_cases')->cascadeOnDelete();
            $table->foreignUuid('uploaded_by')->nullable()->nullOnDelete();
            $table->string('evidence_type', 64);
            $table->string('storage_reference', 255);
            $table->string('checksum', 128)->nullable();
            $table->string('access_class', 32)->default('restricted');
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');
            $table->index(['compliance_case_id', 'created_at']);
        });

        Schema::create('device_registrations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_key_hash', 128);
            $table->string('label', 120)->nullable();
            $table->string('platform', 32)->nullable();
            $table->string('status', 24)->default('active');
            $table->timestampTz('last_seen_at')->nullable();
            $table->timestampTz('registered_at');
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->unique(['user_id', 'device_key_hash']);
            $table->index(['user_id', 'status', 'last_seen_at']);
        });

        Schema::create('auth_security_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->nullOnDelete();
            $table->string('event_type', 80);
            $table->string('severity', 24)->default('low');
            $table->string('ip_address', 45)->nullable();
            $table->string('device_key_hash', 128)->nullable();
            $table->string('correlation_id', 120)->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('occurred_at');
            $table->index(['user_id', 'occurred_at']);
            $table->index(['event_type', 'severity', 'occurred_at']);
        });

        Schema::create('regulatory_report_drafts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('report_type', 64);
            $table->string('jurisdiction', 8)->nullable();
            $table->string('status', 24)->default('draft');
            $table->foreignUuid('created_by')->nullable()->nullOnDelete();
            $table->foreignUuid('approved_by')->nullable()->nullOnDelete();
            $table->text('summary')->nullable();
            $table->json('evidence_references')->nullable();
            $table->json('report_payload')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'created_at']);
        });

        Schema::create('security_policy_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('policy_key', 120);
            $table->unsignedInteger('version');
            $table->string('status', 24)->default('draft');
            $table->json('configuration');
            $table->foreignUuid('published_by')->nullable()->nullOnDelete();
            $table->timestampTz('published_at')->nullable();
            $table->timestampsTz();
            $table->unique(['policy_key', 'version']);
        });
    }

    public function down(): void
    {
        foreach (['security_policy_versions', 'regulatory_report_drafts', 'auth_security_events', 'device_registrations', 'case_evidence', 'case_events', 'compliance_cases', 'screening_matches', 'screening_requests', 'risk_alerts', 'risk_evaluations', 'risk_rule_versions', 'risk_rules', 'customer_risk_profiles'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
