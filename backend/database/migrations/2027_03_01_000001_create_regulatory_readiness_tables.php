<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('regulatory_jurisdictions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 12)->unique();
            $table->string('name', 160);
            $table->string('status', 32)->default('not_assessed');
            $table->json('product_availability')->nullable();
            $table->text('restrictions')->nullable();
            $table->foreignUuid('legal_reviewer_id')->nullable()->nullOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'reviewed_at']);
        });

        Schema::create('regulated_activity_assessments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('jurisdiction_id')->constrained('regulatory_jurisdictions')->cascadeOnDelete();
            $table->string('activity', 80);
            $table->string('status', 32)->default('not_assessed');
            $table->text('conclusion')->nullable();
            $table->foreignUuid('reviewer_id')->nullable()->nullOnDelete();
            $table->string('evidence_reference', 500)->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();
            $table->unique(['jurisdiction_id', 'activity']);
        });

        Schema::create('licensing_requirements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('jurisdiction_id')->constrained('regulatory_jurisdictions')->cascadeOnDelete();
            $table->string('activity', 80);
            $table->string('status', 32)->default('not_assessed');
            $table->foreignUuid('owner_id')->nullable()->nullOnDelete();
            $table->text('requirement_summary')->nullable();
            $table->json('evidence_checklist')->nullable();
            $table->string('source_reference', 500)->nullable();
            $table->timestampTz('review_due_at')->nullable();
            $table->timestampsTz();
            $table->index(['jurisdiction_id', 'status']);
        });

        Schema::create('license_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('jurisdiction_id')->constrained('regulatory_jurisdictions')->cascadeOnDelete();
            $table->foreignUuid('licensing_requirement_id')->nullable()->constrained('licensing_requirements')->nullOnDelete();
            $table->string('activity', 80);
            $table->string('status', 32)->default('not_assessed');
            $table->string('authority', 180)->nullable();
            $table->string('license_reference', 180)->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('evidence_reference', 500)->nullable();
            $table->foreignUuid('verified_by')->nullable()->nullOnDelete();
            $table->timestampsTz();
            $table->index(['jurisdiction_id', 'status', 'expires_at']);
        });

        Schema::create('regulatory_obligations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('jurisdiction_id')->constrained('regulatory_jurisdictions')->cascadeOnDelete();
            $table->string('title', 240);
            $table->string('status', 32)->default('active');
            $table->string('source_reference', 500);
            $table->foreignUuid('owner_id')->nullable()->nullOnDelete();
            $table->json('control_mapping')->nullable();
            $table->string('version', 40)->default('1');
            $table->timestampTz('review_due_at')->nullable();
            $table->string('evidence_reference', 500)->nullable();
            $table->timestampsTz();
            $table->index(['jurisdiction_id', 'status', 'review_due_at']);
        });

        Schema::create('financial_partners', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 200);
            $table->string('partner_type', 40);
            $table->foreignUuid('jurisdiction_id')->nullable()->nullOnDelete();
            $table->string('onboarding_status', 32)->default('prospective');
            $table->string('contract_status', 32)->default('not_reviewed');
            $table->string('api_readiness', 32)->default('not_configured');
            $table->string('risk_classification', 24)->default('unassessed');
            $table->foreignUuid('owner_id')->nullable()->nullOnDelete();
            $table->json('capabilities')->nullable();
            $table->boolean('sandbox_only')->default(true);
            $table->timestampsTz();
            $table->index(['partner_type', 'onboarding_status']);
            $table->index(['sandbox_only', 'api_readiness']);
        });

        Schema::create('partner_due_diligence', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('partner_id')->constrained('financial_partners')->cascadeOnDelete();
            $table->string('status', 32)->default('not_started');
            $table->json('checklist')->nullable();
            $table->foreignUuid('reviewer_id')->nullable()->nullOnDelete();
            $table->string('evidence_reference', 500)->nullable();
            $table->timestampTz('review_due_at')->nullable();
            $table->timestampsTz();
            $table->index(['partner_id', 'status']);
        });

        Schema::create('partner_capability_approvals', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('partner_id')->constrained('financial_partners')->cascadeOnDelete();
            $table->string('capability', 64);
            $table->string('environment', 32)->default('sandbox');
            $table->string('status', 32)->default('evidence_pending');
            $table->string('evidence_reference', 500)->nullable();
            $table->foreignUuid('approval_request_id')->nullable()->constrained('admin_approval_requests')->nullOnDelete();
            $table->foreignUuid('approved_by')->nullable()->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampsTz();
            $table->unique(['partner_id', 'capability', 'environment']);
            $table->index(['environment', 'status']);
        });

        Schema::create('partner_agreements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('partner_id')->constrained('financial_partners')->cascadeOnDelete();
            $table->foreignUuid('jurisdiction_id')->nullable()->nullOnDelete();
            $table->string('status', 32)->default('draft');
            $table->date('effective_date')->nullable();
            $table->date('expires_at')->nullable();
            $table->json('approved_scope')->nullable();
            $table->foreignUuid('owner_id')->nullable()->nullOnDelete();
            $table->string('document_reference', 500)->nullable();
            $table->timestampsTz();
            $table->index(['partner_id', 'status', 'expires_at']);
        });

        Schema::create('safeguarding_reconciliations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('run_reference', 120)->unique();
            $table->char('currency_code', 3);
            $table->foreign('currency_code')->references('code')->on('currencies')->restrictOnDelete();
            $table->bigInteger('expected_amount_minor');
            $table->bigInteger('verified_external_amount_minor')->nullable();
            $table->bigInteger('variance_minor')->nullable();
            $table->string('source_reference', 500)->nullable();
            $table->string('status', 32)->default('pending_evidence');
            $table->foreignUuid('reviewer_id')->nullable()->nullOnDelete();
            $table->timestampTz('reconciled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['currency_code', 'status', 'reconciled_at']);
        });

        Schema::create('safeguarding_exceptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('reconciliation_id')->constrained('safeguarding_reconciliations')->cascadeOnDelete();
            $table->string('classification', 48);
            $table->string('severity', 24)->default('high');
            $table->string('status', 32)->default('open');
            $table->bigInteger('variance_minor');
            $table->foreignUuid('escalated_by')->nullable()->nullOnDelete();
            $table->text('resolution_evidence')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'severity', 'created_at']);
        });

        Schema::create('compliance_assessments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('assessment_type', 64);
            $table->foreignUuid('jurisdiction_id')->nullable()->nullOnDelete();
            $table->string('status', 32)->default('in_progress');
            $table->foreignUuid('owner_id')->nullable()->nullOnDelete();
            $table->foreignUuid('reviewer_id')->nullable()->nullOnDelete();
            $table->string('evidence_reference', 500)->nullable();
            $table->text('summary')->nullable();
            $table->timestampsTz();
            $table->index(['assessment_type', 'status']);
        });

        Schema::create('assurance_findings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('assessment_id')->nullable()->nullOnDelete();
            $table->string('category', 64);
            $table->string('severity', 24)->default('medium');
            $table->string('status', 32)->default('open');
            $table->text('summary');
            $table->text('remediation')->nullable();
            $table->foreignUuid('owner_id')->nullable()->nullOnDelete();
            $table->timestampTz('due_at')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'severity', 'due_at']);
        });

        Schema::create('launch_readiness_gates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('gate_key', 100)->unique();
            $table->string('name', 180);
            $table->string('status', 32)->default('not_started');
            $table->text('evidence_summary')->nullable();
            $table->string('evidence_reference', 500)->nullable();
            $table->foreignUuid('reviewer_id')->nullable()->nullOnDelete();
            $table->foreignUuid('approval_request_id')->nullable()->constrained('admin_approval_requests')->nullOnDelete();
            $table->timestampTz('verified_at')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'verified_at']);
        });

        Schema::create('pilot_authorizations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('pilot_reference', 120)->unique();
            $table->foreignUuid('jurisdiction_id')->nullable()->nullOnDelete();
            $table->string('status', 32)->default('planning');
            $table->json('approved_customer_group')->nullable();
            $table->json('approved_merchant_group')->nullable();
            $table->json('allowed_currencies')->nullable();
            $table->json('allowed_capabilities')->nullable();
            $table->json('risk_limits')->nullable();
            $table->json('provider_dependencies')->nullable();
            $table->text('monitoring_requirements')->nullable();
            $table->text('exit_criteria')->nullable();
            $table->foreignUuid('approval_request_id')->nullable()->constrained('admin_approval_requests')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->nullOnDelete();
            $table->timestampsTz();
            $table->index(['status', 'jurisdiction_id']);
        });

        Schema::create('regulatory_evidence', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('category', 64);
            $table->string('title', 240);
            $table->foreignUuid('jurisdiction_id')->nullable()->nullOnDelete();
            $table->string('source_reference', 500)->nullable();
            $table->foreignUuid('owner_id')->nullable()->nullOnDelete();
            $table->foreignUuid('reviewer_id')->nullable()->nullOnDelete();
            $table->string('document_reference', 500);
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->string('version', 40)->default('1');
            $table->string('approval_status', 32)->default('pending_review');
            $table->string('access_classification', 32)->default('restricted');
            $table->timestampsTz();
            $table->index(['category', 'approval_status', 'expires_at']);
        });
    }

    public function down(): void
    {
        foreach ([
            'regulatory_evidence', 'pilot_authorizations', 'launch_readiness_gates',
            'assurance_findings', 'compliance_assessments', 'safeguarding_exceptions',
            'safeguarding_reconciliations', 'partner_agreements', 'partner_capability_approvals',
            'partner_due_diligence', 'financial_partners', 'regulatory_obligations',
            'license_records', 'licensing_requirements', 'regulated_activity_assessments',
            'regulatory_jurisdictions',
        ] as $table) Schema::dropIfExists($table);
    }
};
