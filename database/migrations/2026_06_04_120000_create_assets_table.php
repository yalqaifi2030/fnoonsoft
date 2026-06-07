<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug', 16)->unique();          // short share id → /d/{slug}
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('upload_session_id')->nullable()->constrained('upload_sessions')->nullOnDelete();

            $table->string('kind', 12)->default('file');    // file | image | pdf
            $table->string('disk', 12)->default('public');  // public | local | r2
            $table->string('path');                         // storage path / r2 key
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum_sha256', 64)->nullable();

            // image specifics
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->json('variants')->nullable();           // {thumb,medium,webp} => {path,w,h}
            $table->unsignedSmallInteger('pages')->nullable(); // pdf page count

            // protection + lifecycle
            $table->string('password')->nullable();         // hashed
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);

            // engagement
            $table->unsignedBigInteger('downloads_count')->default(0);
            $table->unsignedBigInteger('views_count')->default(0);

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'kind']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
