<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->json('title');            // translatable
            $table->string('slug')->unique();
            $table->json('body')->nullable(); // translatable rich html
            $table->boolean('is_published')->default(true);
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
