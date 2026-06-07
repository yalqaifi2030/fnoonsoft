<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('upload_sessions', function (Blueprint $table) {
            // Which backend stored this object: 'r2' (Cloudflare) or 'local' (dev fallback).
            $table->string('storage_disk', 20)->default('r2')->after('r2_upload_id');
        });
    }

    public function down(): void
    {
        Schema::table('upload_sessions', function (Blueprint $table) {
            $table->dropColumn('storage_disk');
        });
    }
};
