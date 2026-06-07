<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_id')->constrained('software')->cascadeOnDelete();
            $table->string('os');                 // windows|macos|linux|android|ios
            $table->string('tier')->default('minimum'); // minimum|recommended
            $table->string('processor')->nullable();
            $table->string('memory')->nullable();
            $table->string('storage')->nullable();
            $table->string('graphics')->nullable();
            $table->string('os_version')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_requirements');
    }
};
