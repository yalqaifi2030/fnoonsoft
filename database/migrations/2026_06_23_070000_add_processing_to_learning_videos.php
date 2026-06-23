<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_videos', function (Blueprint $table) {
            $table->string('thumbnail_path')->nullable()->after('file_path'); // ffmpeg-generated poster
            $table->boolean('is_processing')->default(false)->after('thumbnail_path');
        });
    }

    public function down(): void
    {
        Schema::table('learning_videos', function (Blueprint $table) {
            $table->dropColumn(['thumbnail_path', 'is_processing']);
        });
    }
};
