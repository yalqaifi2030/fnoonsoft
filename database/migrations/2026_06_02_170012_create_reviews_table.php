<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_id')->constrained('software')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');     // 1..5
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('status')->default('pending'); // pending|approved|rejected
            $table->timestamps();

            $table->unique(['software_id', 'user_id']);
            $table->index(['software_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
