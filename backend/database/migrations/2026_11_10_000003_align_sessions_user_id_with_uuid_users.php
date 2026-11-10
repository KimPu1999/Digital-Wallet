<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        if (!Schema::hasTable('sessions') || !Schema::hasColumn('sessions', 'user_id')) {
            return;
        }

        // The users table uses UUID identifiers. Existing Laravel's default
        // sessions migration created user_id as BIGINT, which causes a
        // PostgreSQL cast error when a UUID-authenticated session is saved.
        // Clearing the old session ownership logs out stale sessions safely;
        // it does not modify users, wallets, ledger records, or audit history.
        DB::statement('ALTER TABLE sessions ALTER COLUMN user_id DROP DEFAULT');
        DB::statement('ALTER TABLE sessions ALTER COLUMN user_id TYPE uuid USING NULL::uuid');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        if (!Schema::hasTable('sessions') || !Schema::hasColumn('sessions', 'user_id')) {
            return;
        }

        DB::statement('ALTER TABLE sessions ALTER COLUMN user_id TYPE bigint USING NULL::bigint');
    }
};
