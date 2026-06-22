<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('software', function (Blueprint $table) {
            $table->boolean('notice_enabled')->default(false);
            $table->string('notice_type', 16)->nullable();   // info | warning | promo | success
            $table->json('notice_text')->nullable();         // translatable
            $table->string('notice_url', 500)->nullable();   // optional CTA link
        });
    }

    public function down(): void
    {
        Schema::table('software', function (Blueprint $table) {
            $table->dropColumn(['notice_enabled', 'notice_type', 'notice_text', 'notice_url']);
        });
    }
};
