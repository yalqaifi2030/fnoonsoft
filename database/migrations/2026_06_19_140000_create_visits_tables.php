<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_id', 40)->nullable()->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->char('country', 2)->nullable()->index();
            $table->string('region', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('browser', 40)->nullable();
            $table->string('browser_version', 20)->nullable();
            $table->string('os', 40)->nullable();
            $table->string('device', 12)->nullable()->index();   // desktop | mobile | tablet | bot
            $table->boolean('is_bot')->default(false)->index();
            $table->string('path', 255)->nullable()->index();
            $table->string('referer_host', 120)->nullable();
            $table->foreignId('user_id')->nullable()->index();
            $table->timestamp('created_at')->nullable()->index();
        });

        // Per-IP geolocation cache so each IP is resolved by the external API once.
        Schema::create('ip_locations', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->char('country', 2)->nullable();
            $table->string('country_name', 80)->nullable();
            $table->string('region', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('isp', 120)->nullable();
            $table->boolean('is_proxy')->default(false);
            $table->timestamp('resolved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
        Schema::dropIfExists('ip_locations');
    }
};
