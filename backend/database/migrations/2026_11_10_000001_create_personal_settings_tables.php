<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('default_currency', 3)->nullable();
            $table->uuid('preferred_wallet_id')->nullable();
            $table->boolean('balance_visible')->default(true);
            $table->string('locale', 10)->default('en');
            $table->string('theme', 12)->default('system');
            $table->string('timezone', 64)->default('Asia/Yangon');
            $table->string('date_format', 32)->default('medium');
            $table->string('number_format', 32)->default('locale');
            $table->timestampsTz();
            $table->index(['user_id', 'locale']);
        });

        Schema::table('customer_profiles', function (Blueprint $table): void {
            $table->string('display_name', 120)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('address_line1', 180)->nullable();
            $table->string('address_line2', 180)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('postal_code', 24)->nullable();
            $table->string('avatar_path', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table): void {
            $table->dropColumn(['display_name', 'date_of_birth', 'address_line1', 'address_line2', 'city', 'postal_code', 'avatar_path']);
        });
        Schema::dropIfExists('user_preferences');
    }
};
