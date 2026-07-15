<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->index();
            $table->string('type', 40)->index();          // sqli, xss, traversal, lfi, rce, scanner_ua, honeypot, bruteforce
            $table->string('severity', 12)->index();       // critical | high | medium | low
            $table->string('method', 10)->nullable();
            $table->string('path', 1000)->nullable();
            $table->string('detail', 1000)->nullable();    // matched signature + sanitized snippet
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('country', 2)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->boolean('blocked')->default(false);    // did this event trigger a block?
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->unique();
            $table->string('reason', 255)->nullable();
            $table->string('type', 40)->nullable();        // the trigger type
            $table->boolean('auto')->default(true);        // auto-detected vs manual
            $table->unsignedInteger('hits')->default(1);   // times this IP tripped a block
            $table->timestamp('expires_at')->nullable();   // null = permanent
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
        Schema::dropIfExists('blocked_ips');
    }
};
