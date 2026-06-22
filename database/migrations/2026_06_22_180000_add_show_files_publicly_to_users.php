<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Private by default: a member's shared files show on /u/{username}
            // only to themselves, unless they opt in to a public showcase.
            $table->boolean('show_files_publicly')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('show_files_publicly');
        });
    }
};
