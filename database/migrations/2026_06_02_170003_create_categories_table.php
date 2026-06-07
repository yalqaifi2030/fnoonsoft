<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->json('name');                 // translatable
            $table->string('slug')->unique();
            $table->json('description')->nullable(); // translatable
            $table->string('icon')->nullable();   // font-awesome class or image
            // which content types live under this category
            $table->string('content_type')->nullable(); // application|script|template|plugin|null(any)
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['parent_id', 'content_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
