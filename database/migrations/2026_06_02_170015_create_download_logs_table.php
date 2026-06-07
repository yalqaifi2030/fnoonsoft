<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('download_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_id')->constrained('software')->cascadeOnDelete();
            $table->foreignId('download_link_id')->nullable()->constrained('download_links')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['software_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_logs');
    }
};
