<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Admin access is now enforced per permission (StaffPermissionPolicy). Support
 * inbox models (tickets, contact, program requests, newsletter) get their own
 * permission, granted to every non-author staff role that used them before.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'manage support', 'guard_name' => 'web']);

        foreach (['super_admin', 'editor', 'moderator'] as $name) {
            Role::where('name', $name)->first()?->givePermissionTo('manage support');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'manage support')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
