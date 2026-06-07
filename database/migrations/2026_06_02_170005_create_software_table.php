<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('software', function (Blueprint $table) {
            $table->id();

            // Content type drives which extra fields/UI apply.
            $table->string('content_type')->default('application');
            // application | script | template | plugin

            $table->json('name');                      // translatable
            $table->string('slug')->unique();
            $table->json('short_description')->nullable(); // translatable, plain text
            $table->json('description')->nullable();        // translatable, rich html

            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('developer_id')->nullable()->constrained('developers')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // uploader

            $table->string('icon')->nullable();        // logo/icon path
            $table->string('current_version')->nullable();

            // Cross-type attributes
            $table->json('os_support')->nullable();    // ["windows","macos","linux","android","ios"]
            $table->string('license_type')->default('free'); // free|trial|open_source|paid
            $table->decimal('price', 10, 2)->nullable();     // for paid items
            $table->json('languages')->nullable();           // supported UI languages of the product

            // Type-specific metadata (programming_language, framework, demo_url, platform, ...)
            $table->json('meta')->nullable();

            // Trust & moderation
            $table->string('status')->default('draft'); // draft|pending|published|rejected
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_editor_choice')->default(false);
            $table->boolean('is_malware_free')->default(false);

            // Denormalised counters / scores (kept in sync via events/jobs)
            $table->unsignedBigInteger('downloads_count')->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedBigInteger('views_count')->default(0);

            // SEO
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['content_type', 'status']);
            $table->index(['category_id', 'status']);
            $table->index('is_featured');
            $table->index('downloads_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('software');
    }
};
