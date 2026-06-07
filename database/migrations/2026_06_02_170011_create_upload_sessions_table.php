<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upload_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('software_id')->nullable()->constrained('software')->nullOnDelete();

            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes');

            // R2 multipart upload coordinates
            $table->string('r2_key');                    // object key in bucket
            $table->string('r2_upload_id')->nullable();  // multipart UploadId from R2

            $table->unsignedInteger('part_size')->default(16777216); // 16MB
            $table->unsignedInteger('parts_total')->default(0);
            $table->unsignedInteger('parts_completed')->default(0);
            // [{PartNumber:1, ETag:"..."}, ...] collected as parts finish
            $table->json('parts')->nullable();

            // pending -> uploaded -> scanning -> published | failed
            $table->string('status')->default('pending');
            $table->string('checksum_sha256', 64)->nullable();
            $table->string('checksum_md5', 32)->nullable();
            $table->string('scan_result')->nullable();   // clean|infected|skipped|error
            $table->text('scan_report')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // abandon cleanup horizon
            $table->timestamps();

            $table->index(['status', 'expires_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_sessions');
    }
};
