<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->string('term');
            $table->unsignedInteger('results_count')->default(0);
            $table->unsignedInteger('hits')->default(1); // how many times searched
            $table->timestamps();

            $table->index('term');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};
