<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database for production.
     *
     * Seeds essential data:
     * - Roles and permissions
     * - Languages (English & Dhivehi)
     * - Workflow configuration
     * - Navigation menus (header, footer, social)
     * - Default post types
     * - One sample user per role
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            LanguageSeeder::class,
            WorkflowSeeder::class,
            MenuSeeder::class,
        ]);

        // Seed default post types
        $this->seedPostTypes();

        // Create one user per role type
        $this->createSampleUsers();

        $this->command->info('Production database seeded successfully!');

        // Seed development data in local environment
        if (app()->environment('local')) {
            $this->call(DevelopmentSeeder::class);
        }
    }

    /**
     * Seed default post types configuration.
     */
    protected function seedPostTypes(): void
    {
        // Use the defaults from Setting model
        Setting::setPostTypes(Setting::getDefaultPostTypes());

        $this->command->info('Default post types seeded.');
    }

    /**
     * Create one sample user for each role.
     */
    protected function createSampleUsers(): void
    {
        $password = Hash::make('password');

        // Admin user
        $admin = User::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'username' => 'admin',
            'email_verified_at' => now(),
            'password' => $password,
        ]);
        $admin->assignRole('Admin');

        // Developer user
        $developer = User::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Developer User',
            'email' => 'developer@example.com',
            'username' => 'developer',
            'email_verified_at' => now(),
            'password' => $password,
        ]);
        $developer->assignRole('Developer');

        // Editor user
        $editor = User::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Editor User',
            'email' => 'editor@example.com',
            'username' => 'editor',
            'email_verified_at' => now(),
            'password' => $password,
        ]);
        $editor->assignRole('Editor');

        // Writer user
        $writer = User::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Writer User',
            'email' => 'writer@example.com',
            'username' => 'writer',
            'email_verified_at' => now(),
            'password' => $password,
        ]);
        $writer->assignRole('Writer');

        // Photographer user
        $photographer = User::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Photographer User',
            'email' => 'photographer@example.com',
            'username' => 'photographer',
            'email_verified_at' => now(),
            'password' => $password,
        ]);
        $photographer->assignRole('Photographer');

        $this->command->info('Sample users created (one per role).');
    }
}
