<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Per-member storage quota in GB. NULL = use the global default
            // (member_quota_gb). Lets an admin raise/lower a single member's space.
            $table->decimal('quota_gb', 8, 2)->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('quota_gb');
        });
    }
};
