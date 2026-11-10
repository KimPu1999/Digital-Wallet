<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mobile_builds', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('platform', 16);
            $table->string('channel', 32);
            $table->string('version_name', 40);
            $table->unsignedInteger('version_code');
            $table->string('commit_sha', 80)->nullable();
            $table->string('status', 24);
            $table->string('artifact_uri', 255)->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['platform', 'channel', 'status', 'created_at']);
        });

        Schema::create('mobile_crash_reports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('platform', 16);
            $table->string('release_version', 40);
            $table->string('fingerprint', 128);
            $table->string('severity', 20);
            $table->unsignedInteger('occurrence_count')->default(0);
            $table->string('status', 24)->default('open');
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->unique(['platform', 'release_version', 'fingerprint']);
            $table->index(['severity', 'status', 'created_at']);
        });

        Schema::create('mobile_feedback', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('platform', 16);
            $table->string('release_version', 40);
            $table->string('category', 40);
            $table->string('severity', 20)->default('normal');
            $table->string('status', 24)->default('open');
            $table->text('redacted_summary');
            $table->string('tester_reference', 80)->nullable();
            $table->timestampsTz();
            $table->index(['status', 'severity', 'created_at']);
        });

        Schema::create('mobile_release_candidates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('version_name', 40);
            $table->unsignedInteger('version_code');
            $table->string('channel', 32);
            $table->string('status', 24)->default('proposed');
            $table->foreignUuid('android_build_id')->nullable()->constrained('mobile_builds')->nullOnDelete();
            $table->foreignUuid('ios_build_id')->nullable()->constrained('mobile_builds')->nullOnDelete();
            $table->boolean('approval_required')->default(true);
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->unique(['channel', 'version_code']);
            $table->index(['channel', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_release_candidates');
        Schema::dropIfExists('mobile_feedback');
        Schema::dropIfExists('mobile_crash_reports');
        Schema::dropIfExists('mobile_builds');
    }
};
