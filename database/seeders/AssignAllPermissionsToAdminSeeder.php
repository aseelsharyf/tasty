<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Safe seeder for production - only assigns all existing permissions to Admin role.
 * Does NOT create new permissions, roles, or modify other roles.
 */
class AssignAllPermissionsToAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Find Admin role
        $adminRole = Role::where('name', 'Admin')->first();

        if (! $adminRole) {
            $this->command->error('Admin role not found. Please run RolesAndPermissionsSeeder first.');

            return;
        }

        // Get all existing permissions
        $allPermissions = Permission::all();

        if ($allPermissions->isEmpty()) {
            $this->command->warn('No permissions found in the database.');

            return;
        }

        // Sync all permissions to Admin role
        $adminRole->syncPermissions($allPermissions);

        $this->command->info("Assigned {$allPermissions->count()} permissions to Admin role.");
    }
}
