<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('report_definitions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 80);
            $table->string('name', 160);
            $table->string('report_type', 48);
            $table->unsignedInteger('version')->default(1);
            $table->string('scope', 24)->default('platform');
            $table->json('definition');
            $table->string('status', 24)->default('active');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['code', 'version']);
            $table->index(['report_type', 'scope', 'status']);
        });

        Schema::create('report_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_definition_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('scope', 24)->default('platform');
            $table->string('status', 24)->default('queued');
            $table->json('parameters')->nullable();
            $table->json('result_summary')->nullable();
            $table->string('result_checksum', 128)->nullable();
            $table->string('error_message', 255)->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampsTz();
            $table->index(['scope', 'status', 'created_at']);
            $table->index(['requested_by', 'created_at']);
        });

        Schema::create('report_files', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_run_id')->constrained()->cascadeOnDelete();
            $table->string('format', 12);
            $table->string('storage_key', 255);
            $table->string('checksum', 128);
            $table->unsignedBigInteger('row_count')->default(0);
            $table->string('status', 24)->default('ready');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampsTz();
            $table->index(['report_run_id', 'format', 'status']);
        });

        Schema::create('report_schedules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_definition_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->string('frequency', 24);
            $table->json('parameters')->nullable();
            $table->json('recipients')->nullable();
            $table->string('status', 24)->default('active');
            $table->timestampTz('next_run_at')->nullable();
            $table->timestampTz('last_run_at')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'next_run_at']);
        });

        Schema::create('report_permissions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_definition_id')->constrained()->cascadeOnDelete();
            $table->string('permission', 80);
            $table->timestampsTz();
            $table->unique(['report_definition_id', 'permission']);
        });

        Schema::create('report_audit_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_run_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['action', 'created_at']);
        });

        Schema::create('report_valuations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->char('reporting_currency_code', 3);
            $table->char('source_currency_code', 3);
            $table->decimal('rate', 36, 18);
            $table->string('rate_source', 80);
            $table->timestampTz('rate_timestamp');
            $table->string('rate_version', 80);
            $table->date('valuation_date');
            $table->string('rounding_policy', 40);
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['reporting_currency_code', 'source_currency_code', 'valuation_date']);
        });

        Schema::create('financial_period_closes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 24)->default('open');
            $table->foreignUuid('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('closed_at')->nullable();
            $table->string('snapshot_checksum', 128)->nullable();
            $table->timestampsTz();
            $table->unique(['period_start', 'period_end']);
            $table->index(['status', 'period_end']);
        });

        Schema::create('financial_report_snapshots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_run_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('definition_version');
            $table->date('period_start');
            $table->date('period_end');
            $table->char('reporting_currency_code', 3)->nullable();
            $table->string('source_data_watermark', 160);
            $table->json('valuation_references')->nullable();
            $table->timestampTz('generated_at');
            $table->string('result_checksum', 128);
            $table->foreignUuid('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['report_run_id']);
            $table->index(['period_start', 'period_end', 'reporting_currency_code']);
        });
    }

    public function down(): void
    {
        foreach (['financial_report_snapshots', 'financial_period_closes', 'report_valuations', 'report_audit_events', 'report_permissions', 'report_schedules', 'report_files', 'report_runs', 'report_definitions'] as $table) Schema::dropIfExists($table);
    }
};
