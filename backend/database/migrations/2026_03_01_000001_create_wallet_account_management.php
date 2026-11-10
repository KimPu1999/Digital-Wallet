<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table): void {
            $table->string('symbol', 8)->default('')->after('name');
            $table->unsignedSmallInteger('display_order')->default(100)->after('is_active');
        });

        Schema::create('wallet_owners', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('merchant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('owner_type', 16);
            $table->string('status', 24)->default('active');
            $table->timestamps();
            $table->unique(['user_id', 'owner_type', 'merchant_id']);
            $table->index(['owner_type', 'status']);
        });

        Schema::table('wallets', function (Blueprint $table): void {
            $table->foreignUuid('owner_id')->nullable()->after('id')->constrained('wallet_owners')->cascadeOnDelete();
            $table->string('nickname', 80)->nullable()->after('label');
            $table->unique('owner_id');
            $table->index(['owner_id', 'status']);
        });

        Schema::create('wallet_currency_accounts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->string('status', 24)->default('active');
            $table->timestamps();
            $table->unique(['wallet_id', 'currency_id']);
            $table->index(['wallet_id', 'status']);
        });

        Schema::create('wallet_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('wallet_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('preferred_currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->string('locale', 10)->default('en');
            $table->timestamps();
        });

        Schema::create('wallet_status_history', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('actor_id')->nullable()->nullOnDelete();
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->string('reason', 255)->nullable();
            $table->timestamps();
            $table->index(['wallet_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_status_history');
        Schema::dropIfExists('wallet_preferences');
        Schema::dropIfExists('wallet_currency_accounts');
        Schema::table('wallets', function (Blueprint $table): void { $table->dropForeign(['owner_id']); $table->dropUnique(['owner_id']); $table->dropIndex(['owner_id', 'status']); $table->dropColumn(['owner_id', 'nickname']); });
        Schema::dropIfExists('wallet_owners');
        Schema::table('currencies', function (Blueprint $table): void { $table->dropColumn(['symbol', 'display_order']); });
    }
};
