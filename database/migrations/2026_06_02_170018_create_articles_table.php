<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_category_id')->nullable()->constrained('article_categories')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('title');               // translatable
            $table->string('slug')->unique();
            $table->json('excerpt')->nullable(); // translatable
            $table->json('body')->nullable();    // translatable rich html
            $table->string('cover_image')->nullable();
            $table->string('status')->default('draft'); // draft|published
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
