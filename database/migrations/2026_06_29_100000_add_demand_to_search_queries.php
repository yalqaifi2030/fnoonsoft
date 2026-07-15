<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('search_queries', function (Blueprint $table) {
            $table->unsignedInteger('request_count')->default(0)->after('hits'); // explicit "request this program" clicks
            $table->timestamp('last_searched_at')->nullable()->after('request_count');
            $table->index('hits');
            $table->index('results_count');
        });
    }

    public function down(): void
    {
        Schema::table('search_queries', function (Blueprint $table) {
            $table->dropColumn(['request_count', 'last_searched_at']);
        });
    }
};
