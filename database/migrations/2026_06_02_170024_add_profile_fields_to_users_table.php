<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('email');
            $table->string('country', 2)->nullable()->after('avatar');
            $table->string('locale', 5)->default('en')->after('country');
            $table->boolean('is_active')->default(true)->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'country', 'locale', 'is_active']);
        });
    }
};
