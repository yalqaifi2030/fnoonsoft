<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage software', 'manage categories', 'manage developers',
            'manage reviews', 'manage articles', 'manage pages',
            'manage users', 'manage settings', 'upload files',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $roles = [
            'super_admin' => $permissions, // everything
            'editor' => ['manage software', 'manage categories', 'manage developers', 'manage reviews', 'manage articles', 'upload files'],
            'author' => ['manage software', 'upload files'],
            'moderator' => ['manage reviews'],
        ];

        foreach ($roles as $name => $perms) {
            $role = Role::firstOrCreate(['name' => $name]);
            $role->syncPermissions($perms);
        }

        $admin = User::updateOrCreate(
            ['email' => 'admin@fnoon.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
                'locale' => 'en',
            ],
        );
        $admin->syncRoles(['super_admin']);

        $author = User::updateOrCreate(
            ['email' => 'author@fnoon.test'],
            [
                'name' => 'Demo Author',
                'password' => Hash::make('password'),
                'is_active' => true,
                'locale' => 'en',
            ],
        );
        $author->syncRoles(['author']);
    }
}
