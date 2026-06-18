<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Reviews can now be left by guests after a download — not only by
            // admin-linked registered users.
            $table->string('author_name', 80)->nullable()->after('user_id');
        });

        // user_id becomes optional (guest reviews). Raw SQL keeps it portable
        // without requiring doctrine/dbal for the column change.
        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE reviews MODIFY user_id BIGINT UNSIGNED NULL');
        } catch (\Throwable $e) {
            // Some drivers already allow null / differ — never block the migration.
        }
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('author_name');
        });
    }
};
