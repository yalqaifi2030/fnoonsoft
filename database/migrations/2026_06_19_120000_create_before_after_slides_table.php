<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('before_after_slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_id')->constrained('software')->cascadeOnDelete();
            $table->string('media_type', 12)->default('image'); // image | video
            $table->string('before_path');
            $table->string('after_path');
            $table->string('before_label')->nullable();
            $table->string('after_label')->nullable();
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['software_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('before_after_slides');
    }
};
