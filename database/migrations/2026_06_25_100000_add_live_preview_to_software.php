<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('software', function (Blueprint $table) {
            // URL to a hosted, interactive web build of the app (e.g. Flutter web)
            // shown live inside a phone frame on the product page.
            $table->string('live_preview_url')->nullable()->after('model_poster');
        });
    }

    public function down(): void
    {
        Schema::table('software', function (Blueprint $table) {
            $table->dropColumn('live_preview_url');
        });
    }
};
