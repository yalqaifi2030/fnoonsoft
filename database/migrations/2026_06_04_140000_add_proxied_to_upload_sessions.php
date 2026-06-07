<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('upload_sessions', function (Blueprint $table) {
            // When true, chunks are relayed through the server to S3 (no browser CORS needed).
            $table->boolean('proxied')->default(false)->after('storage_disk');
        });
    }

    public function down(): void
    {
        Schema::table('upload_sessions', function (Blueprint $table) {
            $table->dropColumn('proxied');
        });
    }
};
