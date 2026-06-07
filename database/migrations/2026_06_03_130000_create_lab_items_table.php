<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interactive_lab_id')->constrained('interactive_labs')->cascadeOnDelete();
            $table->json('title');                  // translatable
            $table->json('description')->nullable(); // translatable
            $table->json('data')->nullable();       // flexible payload per lab type
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_items');
    }
};
