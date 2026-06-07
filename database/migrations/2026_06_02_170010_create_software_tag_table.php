<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('software_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_id')->constrained('software')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->unique(['software_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('software_tag');
    }
};
