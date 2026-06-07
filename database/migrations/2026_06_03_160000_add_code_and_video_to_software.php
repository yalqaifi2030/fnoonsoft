<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('software', function (Blueprint $table) {
            // Code viewer
            $table->longText('code')->nullable()->after('features');
            $table->string('code_language')->nullable()->after('code');

            // Explanation video (multi-source: youtube | external | upload)
            $table->string('video_source')->nullable()->after('code_language');
            $table->string('video_url')->nullable()->after('video_source');
            $table->string('video_path')->nullable()->after('video_url');
        });
    }

    public function down(): void
    {
        Schema::table('software', function (Blueprint $table) {
            $table->dropColumn(['code', 'code_language', 'video_source', 'video_url', 'video_path']);
        });
    }
};
