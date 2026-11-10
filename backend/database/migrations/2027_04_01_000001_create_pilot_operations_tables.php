<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pilot_programs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('pilot_reference', 120)->unique();
            $table->string('name', 180);
            $table->text('objective');
            $table->string('environment', 32)->default('sandbox');
            $table->string('status', 32)->default('draft');
            $table->json('jurisdictions')->nullable();
            $table->json('supported_currencies')->nullable();
            $table->json('allowed_features')->nullable();
            $table->json('provider_capabilities')->nullable();
            $table->json('transaction_types')->nullable();
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->foreignUuid('owner_id')->nullable()->nullOnDelete();
            $table->foreignUuid('reviewer_id')->nullable()->nullOnDelete();
            $table->foreignUuid('pilot_authorization_id')->nullable()->constrained('pilot_authorizations')->nullOnDelete();
            $table->foreignUuid('approval_request_id')->nullable()->constrained('admin_approval_requests')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->boolean('sandbox_only')->default(true);
            $table->timestampsTz();
            $table->index(['environment', 'status', 'starts_at', 'ends_at']);
        });

        Schema::create('pilot_cohorts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pilot_program_id')->constrained('pilot_programs')->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('cohort_type', 24);
            $table->string('status', 24)->default('active');
            $table->json('eligibility_requirements')->nullable();
            $table->unsignedInteger('max_participants')->nullable();
            $table->foreignUuid('created_by')->nullable()->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['pilot_program_id', 'name']);
            $table->index(['pilot_program_id', 'cohort_type', 'status']);
        });

        Schema::create('pilot_participants', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pilot_program_id')->constrained('pilot_programs')->cascadeOnDelete();
            $table->foreignUuid('cohort_id')->nullable()->constrained('pilot_cohorts')->nullOnDelete();
            $table->string('subject_type', 24);
            $table->uuid('subject_id');
            $table->string('status', 24)->default('invited');
            $table->string('eligibility_status', 24)->default('pending');
            $table->foreignUuid('invited_by')->nullable()->nullOnDelete();
            $table->timestampTz('invited_at')->nullable();
            $table->timestampTz('enrolled_at')->nullable();
            $table->timestampTz('activated_at')->nullable();
            $table->timestampTz('removed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->unique(['pilot_program_id', 'subject_type', 'subject_id']);
            $table->index(['pilot_program_id', 'status', 'eligibility_status']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('pilot_scope_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pilot_program_id')->constrained('pilot_programs')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status', 24)->default('draft');
            $table->json('jurisdictions')->nullable();
            $table->json('currencies')->nullable();
            $table->json('features')->nullable();
            $table->json('provider_capabilities')->nullable();
            $table->json('transaction_types')->nullable();
            $table->foreignUuid('created_by')->nullable()->nullOnDelete();
            $table->foreignUuid('approval_request_id')->nullable()->constrained('admin_approval_requests')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['pilot_program_id', 'version']);
            $table->index(['pilot_program_id', 'status']);
        });

        Schema::create('pilot_eligibility_checks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('participant_id')->constrained('pilot_participants')->cascadeOnDelete();
            $table->string('check_type', 48);
            $table->string('status', 24);
            $table->string('reason', 500)->nullable();
            $table->string('evidence_reference', 500)->nullable();
            $table->foreignUuid('checked_by')->nullable()->nullOnDelete();
            $table->timestampTz('checked_at')->nullable();
            $table->timestampsTz();
            $table->unique(['participant_id', 'check_type']);
            $table->index(['participant_id', 'status']);
        });

        Schema::create('pilot_limits', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pilot_program_id')->constrained('pilot_programs')->cascadeOnDelete();
            $table->char('currency_code', 3)->nullable();
            $table->foreign('currency_code')->references('code')->on('currencies')->nullOnDelete();
            $table->unsignedBigInteger('max_transaction_minor')->nullable();
            $table->unsignedBigInteger('daily_volume_minor')->nullable();
            $table->unsignedInteger('daily_activity_count')->nullable();
            $table->unsignedInteger('transfer_frequency_per_day')->nullable();
            $table->unsignedInteger('merchant_payment_count_per_day')->nullable();
            $table->unsignedInteger('fx_conversion_count_per_day')->nullable();
            $table->string('status', 24)->default('active');
            $table->foreignUuid('approved_by')->nullable()->nullOnDelete();
            $table->foreignUuid('approval_request_id')->nullable()->constrained('admin_approval_requests')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['pilot_program_id', 'currency_code']);
            $table->index(['pilot_program_id', 'status']);
        });

        Schema::create('pilot_activity_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pilot_program_id')->constrained('pilot_programs')->cascadeOnDelete();
            $table->foreignUuid('participant_id')->nullable()->constrained('pilot_participants')->nullOnDelete();
            $table->string('event_type', 64);
            $table->string('aggregate_type', 80)->nullable();
            $table->uuid('aggregate_id')->nullable();
            $table->char('currency_code', 3)->nullable();
            $table->foreign('currency_code')->references('code')->on('currencies')->nullOnDelete();
            $table->unsignedBigInteger('amount_minor')->nullable();
            $table->string('status', 32)->default('observed');
            $table->json('metadata')->nullable();
            $table->timestampTz('occurred_at');
            $table->timestampsTz();
            $table->index(['pilot_program_id', 'event_type', 'occurred_at']);
            $table->index(['pilot_program_id', 'currency_code', 'status']);
            $table->index(['aggregate_type', 'aggregate_id']);
        });

        Schema::create('pilot_monitoring_snapshots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pilot_program_id')->constrained('pilot_programs')->cascadeOnDelete();
            $table->string('snapshot_type', 48);
            $table->char('currency_code', 3)->nullable();
            $table->foreign('currency_code')->references('code')->on('currencies')->nullOnDelete();
            $table->unsignedBigInteger('transaction_count')->default(0);
            $table->unsignedBigInteger('volume_minor')->default(0);
            $table->unsignedBigInteger('failed_count')->default(0);
            $table->unsignedBigInteger('pending_count')->default(0);
            $table->unsignedBigInteger('unknown_count')->default(0);
            $table->unsignedBigInteger('exception_count')->default(0);
            $table->timestampTz('generated_at');
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['pilot_program_id', 'snapshot_type', 'generated_at']);
        });

        Schema::create('pilot_reconciliation_reviews', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pilot_program_id')->constrained('pilot_programs')->cascadeOnDelete();
            $table->string('review_type', 48);
            $table->char('currency_code', 3)->nullable();
            $table->foreign('currency_code')->references('code')->on('currencies')->nullOnDelete();
            $table->bigInteger('expected_minor')->nullable();
            $table->bigInteger('actual_minor')->nullable();
            $table->bigInteger('variance_minor')->nullable();
            $table->string('status', 24)->default('pending');
            $table->string('source_reference', 500)->nullable();
            $table->string('evidence_reference', 500)->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->nullOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();
            $table->index(['pilot_program_id', 'review_type', 'currency_code', 'status']);
        });

        Schema::create('pilot_exceptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pilot_program_id')->constrained('pilot_programs')->cascadeOnDelete();
            $table->foreignUuid('reconciliation_review_id')->nullable()->constrained('pilot_reconciliation_reviews')->nullOnDelete();
            $table->string('classification', 64);
            $table->string('severity', 24)->default('medium');
            $table->string('status', 24)->default('open');
            $table->foreignUuid('owner_id')->nullable()->nullOnDelete();
            $table->text('investigation_notes')->nullable();
            $table->string('evidence_reference', 500)->nullable();
            $table->timestampTz('escalated_at')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampsTz();
            $table->index(['pilot_program_id', 'status', 'severity']);
        });

        Schema::create('pilot_success_criteria', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pilot_program_id')->constrained('pilot_programs')->cascadeOnDelete();
            $table->string('criterion_key', 80);
            $table->string('name', 180);
            $table->string('metric', 120);
            $table->string('target_value', 120);
            $table->string('unit', 40)->nullable();
            $table->string('status', 24)->default('pending_approval');
            $table->string('measured_value', 120)->nullable();
            $table->string('evidence_reference', 500)->nullable();
            $table->foreignUuid('owner_id')->nullable()->nullOnDelete();
            $table->foreignUuid('approved_by')->nullable()->nullOnDelete();
            $table->foreignUuid('approval_request_id')->nullable()->constrained('admin_approval_requests')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['pilot_program_id', 'criterion_key']);
            $table->index(['pilot_program_id', 'status']);
        });

        Schema::create('pilot_evaluations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pilot_program_id')->constrained('pilot_programs')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 24)->default('draft');
            $table->json('criteria_comparison')->nullable();
            $table->json('operational_findings')->nullable();
            $table->json('financial_integrity_findings')->nullable();
            $table->json('security_findings')->nullable();
            $table->json('compliance_findings')->nullable();
            $table->text('participant_feedback_summary')->nullable();
            $table->text('outstanding_issues')->nullable();
            $table->text('recommendations')->nullable();
            $table->string('evidence_reference', 500)->nullable();
            $table->foreignUuid('created_by')->nullable()->nullOnDelete();
            $table->foreignUuid('reviewer_id')->nullable()->nullOnDelete();
            $table->foreignUuid('approval_request_id')->nullable()->constrained('admin_approval_requests')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['pilot_program_id', 'status', 'period_end']);
        });

        Schema::create('pilot_decisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pilot_program_id')->constrained('pilot_programs')->cascadeOnDelete();
            $table->string('decision', 32);
            $table->string('status', 24)->default('pending');
            $table->text('reason');
            $table->foreignUuid('requested_by')->nullable()->nullOnDelete();
            $table->foreignUuid('approval_request_id')->nullable()->constrained('admin_approval_requests')->nullOnDelete();
            $table->foreignUuid('decided_by')->nullable()->nullOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->timestampsTz();
            $table->index(['pilot_program_id', 'decision', 'status']);
        });

        Schema::create('pilot_closure_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('pilot_program_id')->unique()->constrained('pilot_programs')->cascadeOnDelete();
            $table->string('status', 24)->default('pending');
            $table->boolean('enrollment_closed')->default(false);
            $table->boolean('capabilities_disabled')->default(false);
            $table->string('final_reconciliation_status', 32)->default('pending');
            $table->boolean('outstanding_incidents_reviewed')->default(false);
            $table->boolean('support_reviewed')->default(false);
            $table->string('evidence_archive_reference', 500)->nullable();
            $table->foreignUuid('approval_request_id')->nullable()->constrained('admin_approval_requests')->nullOnDelete();
            $table->foreignUuid('closed_by')->nullable()->nullOnDelete();
            $table->timestampTz('closed_at')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'final_reconciliation_status']);
        });
    }

    public function down(): void
    {
        foreach ([
            'pilot_closure_records', 'pilot_decisions', 'pilot_evaluations', 'pilot_success_criteria',
            'pilot_exceptions', 'pilot_reconciliation_reviews', 'pilot_monitoring_snapshots',
            'pilot_activity_events', 'pilot_limits', 'pilot_eligibility_checks',
            'pilot_scope_versions', 'pilot_participants', 'pilot_cohorts', 'pilot_programs',
        ] as $table) Schema::dropIfExists($table);
    }
};
