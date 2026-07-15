<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_requests', function (Blueprint $table) {
            $table->id();
            $table->string('term');                              // the program the visitor asked for
            $table->unsignedInteger('votes')->default(1);        // how many people asked for the same thing
            $table->text('note')->nullable();                    // latest extra details from a visitor
            $table->string('contact')->nullable();               // latest email / whatsapp to notify when available
            $table->string('status')->default('new');            // new | sourcing | available | rejected
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->timestamp('last_requested_at')->nullable();
            $table->timestamps();

            $table->unique('term');
            $table->index('status');
            $table->index('votes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_requests');
    }
};
