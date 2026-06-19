<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Allow guest (no-account) reports + carry the report context.
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->string('guest_name')->nullable()->after('user_id');
            $table->string('guest_email')->nullable()->after('guest_name');
            $table->string('source', 40)->nullable()->after('status'); // download | web | error
            $table->json('meta')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['guest_name', 'guest_email', 'source', 'meta']);
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
