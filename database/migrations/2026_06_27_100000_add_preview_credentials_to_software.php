<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('software', function (Blueprint $table) {
            // Demo login shown next to the live preview (not real secrets).
            $table->string('preview_username')->nullable()->after('appetize_public_key');
            $table->string('preview_password')->nullable()->after('preview_username');
        });
    }

    public function down(): void
    {
        Schema::table('software', function (Blueprint $table) {
            $table->dropColumn(['preview_username', 'preview_password']);
        });
    }
};
