<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qa_module_inventory', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('module_key', 100)->unique();
            $table->unsignedSmallInteger('phase');
            $table->string('name', 180);
            $table->string('coverage_status', 32)->default('untested');
            $table->string('evidence_reference', 255)->nullable();
            $table->timestampTz('audited_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['phase', 'coverage_status']);
        });

        Schema::create('maintenance_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('item_key', 100)->unique();
            $table->string('category', 48);
            $table->string('priority', 16)->default('medium');
            $table->string('status', 24)->default('open');
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->foreignUuid('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('review_due_at')->nullable();
            $table->string('evidence_reference', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->index(['category', 'priority', 'status']);
            $table->index(['review_due_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_items');
        Schema::dropIfExists('qa_module_inventory');
    }
};
