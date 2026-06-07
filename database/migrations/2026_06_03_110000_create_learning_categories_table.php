<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name');                 // translatable
            $table->string('slug')->unique();
            $table->json('description')->nullable(); // translatable
            $table->string('icon')->default('fa-solid fa-graduation-cap');
            $table->string('color')->default('from-emerald-500 to-green-700'); // tailwind gradient
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_categories');
    }
};
