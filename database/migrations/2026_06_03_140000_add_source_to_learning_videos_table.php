<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_videos', function (Blueprint $table) {
            $table->string('source')->default('youtube')->after('url'); // youtube|external|upload
            $table->string('file_path')->nullable()->after('source');   // uploaded video path
            $table->string('url')->nullable()->change();                // url optional (uploads have no url)
        });
    }

    public function down(): void
    {
        Schema::table('learning_videos', function (Blueprint $table) {
            $table->dropColumn(['source', 'file_path']);
        });
    }
};
