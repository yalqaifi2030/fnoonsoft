<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('bio', 500)->nullable()->after('avatar');
            $table->string('website')->nullable()->after('bio');
            $table->string('twitter')->nullable()->after('website');
            $table->string('github')->nullable()->after('twitter');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'bio', 'website', 'twitter', 'github']);
        });
    }
};
