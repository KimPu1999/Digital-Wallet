<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('platform_roles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 80)->unique();
            $table->string('guard_name', 32)->default('web');
            $table->timestampsTz();
        });
        Schema::create('platform_permissions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 120)->unique();
            $table->string('guard_name', 32)->default('web');
            $table->timestampsTz();
        });
        Schema::create('platform_admin_memberships', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('platform_role_id')->constrained('platform_roles')->cascadeOnDelete();
            $table->string('status', 24)->default('active');
            $table->timestampTz('last_step_up_at')->nullable();
            $table->timestampsTz();
            $table->unique(['user_id', 'platform_role_id']);
            $table->index(['user_id', 'status']);
        });
        Schema::create('platform_role_permissions', function (Blueprint $table): void {
            $table->foreignUuid('platform_role_id')->constrained('platform_roles')->cascadeOnDelete();
            $table->foreignUuid('platform_permission_id')->constrained('platform_permissions')->cascadeOnDelete();
            $table->timestampsTz();
            $table->primary(['platform_role_id', 'platform_permission_id']);
        });
        Schema::create('admin_approval_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('action', 120);
            $table->string('resource_type', 160)->nullable();
            $table->uuid('resource_id')->nullable();
            $table->string('status', 24)->default('pending');
            $table->unsignedTinyInteger('required_decisions')->default(1);
            $table->timestampTz('expires_at');
            $table->text('reason');
            $table->string('idempotency_key', 255);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->unique(['requested_by', 'idempotency_key']);
            $table->index(['status', 'expires_at']);
            $table->index(['resource_type', 'resource_id']);
        });
        Schema::create('admin_approval_decisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('approval_request_id')->constrained('admin_approval_requests')->cascadeOnDelete();
            $table->foreignUuid('decided_by')->constrained('users')->restrictOnDelete();
            $table->string('decision', 24);
            $table->text('reason')->nullable();
            $table->timestampsTz();
            $table->unique(['approval_request_id', 'decided_by']);
        });
        Schema::create('admin_security_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('actor_id')->nullable()->nullOnDelete();
            $table->string('event_type', 120);
            $table->string('correlation_id', 120)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');
            $table->index(['event_type', 'created_at']);
        });
        Schema::create('platform_audit_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('actor_id')->nullable()->nullOnDelete();
            $table->string('action', 160);
            $table->string('resource_type', 160)->nullable();
            $table->uuid('resource_id')->nullable();
            $table->string('correlation_id', 120)->nullable();
            $table->string('decision', 32)->nullable();
            $table->foreignUuid('approval_request_id')->nullable()->nullOnDelete();
            $table->json('previous_state')->nullable();
            $table->json('new_state')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');
            $table->index(['resource_type', 'resource_id', 'created_at']);
            $table->index(['actor_id', 'created_at']);
        });
        Schema::create('risk_cases', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('subject_type', 80);
            $table->uuid('subject_id');
            $table->foreignUuid('opened_by')->nullable()->nullOnDelete();
            $table->foreignUuid('assigned_to')->nullable()->nullOnDelete();
            $table->string('status', 24)->default('open');
            $table->string('severity', 24)->default('medium');
            $table->string('reason', 255);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['status', 'severity', 'created_at']);
        });
        Schema::create('risk_case_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('risk_case_id')->constrained('risk_cases')->cascadeOnDelete();
            $table->foreignUuid('actor_id')->nullable()->nullOnDelete();
            $table->string('event_type', 80);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');
            $table->index(['risk_case_id', 'created_at']);
        });
        Schema::create('platform_feature_flags', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('flag_key', 120)->unique();
            $table->boolean('enabled')->default(false);
            $table->string('environment', 32)->default('sandbox');
            $table->json('metadata')->nullable();
            $table->timestampsTz();
        });
        Schema::create('platform_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('setting_key', 120)->unique();
            $table->json('value');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('critical')->default(false);
            $table->timestampsTz();
        });
        Schema::create('platform_settings_history', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('setting_key', 120);
            $table->unsignedInteger('version');
            $table->json('value');
            $table->foreignUuid('changed_by')->nullable()->nullOnDelete();
            $table->foreignUuid('approval_request_id')->nullable()->nullOnDelete();
            $table->timestampTz('created_at');
            $table->unique(['setting_key', 'version']);
            $table->index(['setting_key', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['platform_settings_history', 'platform_settings', 'platform_feature_flags', 'risk_case_events', 'risk_cases', 'platform_audit_events', 'admin_security_events', 'admin_approval_decisions', 'admin_approval_requests', 'platform_role_permissions', 'platform_admin_memberships', 'platform_permissions', 'platform_roles'] as $table) Schema::dropIfExists($table);
    }
};
