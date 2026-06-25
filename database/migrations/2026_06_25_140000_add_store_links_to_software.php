<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('software', function (Blueprint $table) {
            $table->string('play_url')->nullable()->after('live_preview_url');     // Google Play
            $table->string('appstore_url')->nullable()->after('play_url');         // Apple App Store
        });
    }

    public function down(): void
    {
        Schema::table('software', function (Blueprint $table) {
            $table->dropColumn(['play_url', 'appstore_url']);
        });
    }
};
