<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('developers', function (Blueprint $table) {
            $table->string('email')->nullable()->after('website');
            $table->string('phone')->nullable()->after('email');     // used for WhatsApp / call
            $table->string('twitter')->nullable()->after('phone');   // X handle or URL
        });
    }

    public function down(): void
    {
        Schema::table('developers', function (Blueprint $table) {
            $table->dropColumn(['email', 'phone', 'twitter']);
        });
    }
};
