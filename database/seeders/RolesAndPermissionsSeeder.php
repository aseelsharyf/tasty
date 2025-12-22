<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions by module
        $permissions = [
            // User Management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Role Management
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',

            // Posts/Content
            'posts.view',
            'posts.create',
            'posts.edit',
            'posts.edit-own',
            'posts.delete',
            'posts.publish',

            // Pages
            'pages.view',
            'pages.create',
            'pages.edit',
            'pages.delete',
            'pages.publish',

            // Media
            'media.view',
            'media.upload',
            'media.edit',
            'media.delete',

            // Categories
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',

            // Tags
            'tags.view',
            'tags.create',
            'tags.edit',
            'tags.delete',

            // Sponsors
            'sponsors.view',
            'sponsors.create',
            'sponsors.edit',
            'sponsors.delete',

            // Menus
            'menus.view',
            'menus.create',
            'menus.edit',
            'menus.delete',

            // Comments
            'comments.view',
            'comments.moderate',
            'comments.edit',
            'comments.delete',
            'comments.manage-bans',

            // Workflow
            'workflow.view',
            'workflow.transition',
            'workflow.comment',
            'workflow.publish',
            'workflow.revert',

            // Settings
            'settings.view',
            'settings.edit',

            // Analytics
            'analytics.view',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Admin role with all permissions
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->syncPermissions(Permission::all());

        // Create Developer role
        $developerRole = Role::firstOrCreate(['name' => 'Developer']);
        $developerRole->syncPermissions([
            'users.view',
            'users.create',
            'users.edit',
            'roles.view',
            'posts.view',
            'posts.create',
            'posts.edit',
            'posts.delete',
            'posts.publish',
            'pages.view',
            'pages.create',
            'pages.edit',
            'pages.delete',
            'pages.publish',
            'media.view',
            'media.upload',
            'media.edit',
            'media.delete',
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',
            'tags.view',
            'tags.create',
            'tags.edit',
            'tags.delete',
            'sponsors.view',
            'sponsors.create',
            'sponsors.edit',
            'sponsors.delete',
            'menus.view',
            'menus.create',
            'menus.edit',
            'menus.delete',
            'comments.view',
            'comments.moderate',
            'comments.edit',
            'comments.delete',
            'comments.manage-bans',
            'workflow.view',
            'workflow.transition',
            'workflow.comment',
            'workflow.publish',
            'workflow.revert',
            'settings.view',
            'settings.edit',
            'analytics.view',
        ]);

        // Create Editor role
        $editorRole = Role::firstOrCreate(['name' => 'Editor']);
        $editorRole->syncPermissions([
            'posts.view',
            'posts.create',
            'posts.edit',
            'posts.delete',
            'posts.publish',
            'pages.view',
            'pages.edit',
            'pages.publish',
            'media.view',
            'media.upload',
            'media.edit',
            'categories.view',
            'categories.create',
            'categories.edit',
            'tags.view',
            'tags.create',
            'tags.edit',
            'sponsors.view',
            'sponsors.create',
            'sponsors.edit',
            'comments.view',
            'comments.moderate',
            'comments.edit',
            'comments.delete',
            'workflow.view',
            'workflow.transition',
            'workflow.comment',
            'workflow.publish',
            'workflow.revert',
        ]);

        // Create Writer role
        $writerRole = Role::firstOrCreate(['name' => 'Writer']);
        $writerRole->syncPermissions([
            'posts.view',
            'posts.create',
            'posts.edit-own',
            'media.view',
            'media.upload',
            'categories.view',
            'tags.view',
            'tags.create',
            'comments.view', // Can view comments on their own posts
            'workflow.view',
            'workflow.transition', // Limited transitions defined in workflow config
            'workflow.comment',
        ]);

        // Create Photographer role
        $photographerRole = Role::firstOrCreate(['name' => 'Photographer']);
        $photographerRole->syncPermissions([
            'media.view',
            'media.upload',
            'media.edit',
            'media.delete',
        ]);

        // Create User role (no CMS access)
        Role::firstOrCreate(['name' => 'User']);

        // Assign Admin role to existing users who have no role
        User::whereDoesntHave('roles')->each(function ($user) use ($adminRole) {
            $user->assignRole($adminRole);
        });
    }
}
