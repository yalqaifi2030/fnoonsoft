<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('software', function (Blueprint $table) {
            // Admin-defined copyable blocks (links, code, keys…) shown on the product page.
            $table->json('copy_blocks')->nullable()->after('live_preview_url');
        });
    }

    public function down(): void
    {
        Schema::table('software', function (Blueprint $table) {
            $table->dropColumn('copy_blocks');
        });
    }
};
