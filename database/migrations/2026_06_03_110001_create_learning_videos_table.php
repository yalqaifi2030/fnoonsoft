<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_category_id')->constrained('learning_categories')->cascadeOnDelete();
            $table->json('title');                  // translatable
            $table->json('description')->nullable(); // translatable
            $table->string('url');                  // YouTube URL or ID
            $table->string('duration')->nullable(); // e.g. "12:30"
            $table->string('level')->default('beginner'); // beginner|intermediate|advanced
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_videos');
    }
};
