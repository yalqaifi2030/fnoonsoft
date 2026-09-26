<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * File moderation: every member upload is reviewed by staff (post-moderation —
 * available immediately, reviewed after). Adds the review state to assets,
 * trusted/banned flags to users, an append-only review history (kept even
 * after a file is purged — legal evidence) and visitor reports.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('moderation_status', 12)->default('pending')->after('is_active'); // pending|approved|rejected
            $table->foreignId('reviewed_by')->nullable()->after('moderation_status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->string('rejection_reason', 40)->nullable()->after('reviewed_at');
            $table->text('rejection_note')->nullable()->after('rejection_reason');
            $table->unsignedInteger('reports_count')->default(0)->after('rejection_note');
            $table->unsignedInteger('open_reports')->default(0)->after('reports_count');
            $table->index(['moderation_status', 'created_at']);
            $table->index('open_reports');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('uploads_trusted')->default(false);
            $table->timestamp('uploads_banned_at')->nullable();
            $table->string('uploads_ban_reason', 500)->nullable();
        });

        Schema::create('asset_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            // Snapshot so the record stays meaningful after the file is purged.
            $table->string('asset_name')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('checksum_sha256', 64)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete(); // null = system
            $table->string('action', 24); // approved|rejected|restored|auto_approved|reported|downloaded|owner_trusted|owner_untrusted|owner_banned|owner_unbanned|purged
            $table->string('reason', 40)->nullable();
            $table->text('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['asset_id', 'created_at']);
            $table->index(['owner_id', 'created_at']);
        });

        Schema::create('asset_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->string('reason', 40);
            $table->text('message')->nullable();
            $table->string('reporter_email', 190)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('status', 12)->default('open'); // open|resolved|dismissed
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['asset_id', 'status']);
        });

        // Existing files: staff / ownerless → approved; members' → queued for review.
        $staffIds = DB::table('model_has_roles')->where('model_type', 'App\\Models\\User')->pluck('model_id');
        DB::table('assets')->whereNull('user_id')->orWhereIn('user_id', $staffIds)->update(['moderation_status' => 'approved']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => 'moderate files', 'guard_name' => 'web']);
        foreach (['super_admin', 'editor', 'moderator'] as $name) {
            Role::where('name', $name)->first()?->givePermissionTo('moderate files');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_reports');
        Schema::dropIfExists('asset_reviews');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['uploads_trusted', 'uploads_banned_at', 'uploads_ban_reason']);
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropIndex(['moderation_status', 'created_at']);
            $table->dropIndex(['open_reports']);
            $table->dropColumn(['moderation_status', 'reviewed_at', 'rejection_reason', 'rejection_note', 'reports_count', 'open_reports']);
        });

        Permission::where('name', 'moderate files')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
