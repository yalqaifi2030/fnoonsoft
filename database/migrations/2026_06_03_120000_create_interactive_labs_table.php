<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interactive_labs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();   // playground|arduino|ai|security|snippets (maps to a component)
            $table->string('slug')->unique();
            $table->json('title');             // translatable
            $table->json('description')->nullable(); // translatable
            $table->string('icon')->default('fa-solid fa-flask');
            $table->string('color')->default('from-emerald-500 to-green-700');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactive_labs');
    }
};
