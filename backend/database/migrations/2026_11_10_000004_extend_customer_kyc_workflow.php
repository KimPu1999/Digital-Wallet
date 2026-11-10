<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('kyc_cases')) {
            Schema::table('kyc_cases', function (Blueprint $table): void {
                if (!Schema::hasColumn('kyc_cases', 'personal_details')) {
                    $table->text('personal_details')->nullable();
                }
                if (!Schema::hasColumn('kyc_cases', 'consented_at')) {
                    $table->timestampTz('consented_at')->nullable();
                }
                if (!Schema::hasColumn('kyc_cases', 'last_customer_action_at')) {
                    $table->timestampTz('last_customer_action_at')->nullable();
                }
            });
        }

        if (!Schema::hasTable('kyc_status_events')) {
            Schema::create('kyc_status_events', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('kyc_case_id')->constrained('kyc_cases')->cascadeOnDelete();
                $table->foreignUuid('actor_id')->nullable()->nullOnDelete();
                $table->string('from_status', 48)->nullable();
                $table->string('to_status', 48);
                $table->string('reason', 255)->nullable();
                $table->json('metadata')->nullable();
                $table->timestampsTz();
                $table->index(['kyc_case_id', 'created_at']);
                $table->index(['to_status', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_status_events');
        if (Schema::hasTable('kyc_cases')) {
            Schema::table('kyc_cases', function (Blueprint $table): void {
                foreach (['personal_details', 'consented_at', 'last_customer_action_at'] as $column) {
                    if (Schema::hasColumn('kyc_cases', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
