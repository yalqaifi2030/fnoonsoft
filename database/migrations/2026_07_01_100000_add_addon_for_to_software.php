<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('software', function (Blueprint $table) {
            // Self-reference: this item is an addon/plugin FOR another program.
            // Null = a standalone program (which may itself host addons).
            $table->foreignId('addon_for_id')->nullable()->after('developer_id')
                ->constrained('software')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('software', function (Blueprint $table) {
            $table->dropConstrainedForeignId('addon_for_id');
        });
    }
};
