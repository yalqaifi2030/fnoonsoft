<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('software', function (Blueprint $table) {
            // Appetize.io public key (or full embed URL) — runs a real APK/IPA
            // in a cloud emulator embedded in the product page's phone frame.
            $table->string('appetize_public_key')->nullable()->after('live_preview_url');
        });
    }

    public function down(): void
    {
        Schema::table('software', function (Blueprint $table) {
            $table->dropColumn('appetize_public_key');
        });
    }
};
