<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('software_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_id')->constrained('software')->cascadeOnDelete();
            $table->string('version');
            $table->json('changelog')->nullable();      // translatable
            $table->date('released_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->boolean('is_beta')->default(false);
            $table->timestamps();

            $table->index(['software_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('software_versions');
    }
};
