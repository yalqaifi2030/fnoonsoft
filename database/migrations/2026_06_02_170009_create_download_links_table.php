<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('download_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_id')->constrained('software')->cascadeOnDelete();
            $table->foreignId('software_version_id')->nullable()->constrained('software_versions')->nullOnDelete();

            $table->string('label')->nullable();           // "Windows 64-bit", "Portable", ...
            $table->string('type')->default('r2');         // r2 (presigned) | external (mirror)
            $table->string('os')->nullable();
            $table->string('architecture')->nullable();    // x64|x86|arm64|universal
            $table->boolean('is_portable')->default(false);

            // For type=r2: object key in the R2 bucket. For type=external: full url.
            $table->string('r2_key')->nullable();
            $table->string('external_url')->nullable();

            $table->string('original_filename')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum_sha256', 64)->nullable();
            $table->string('checksum_md5', 32)->nullable();

            $table->unsignedBigInteger('downloads_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['software_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_links');
    }
};
