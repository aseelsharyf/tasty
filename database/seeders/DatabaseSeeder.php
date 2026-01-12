<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Production seeds:
     * - Roles and permissions
     * - Languages (English & Dhivehi)
     * - Workflow configuration
     * - Navigation menus
     * - Default post types
     * - Basic categories
     * - Sample users (one per role)
     *
     * Development (local only):
     * - Full taxonomy (categories with children, tags)
     * - Sample posts
     * - Sample media
     * - Comments
     */
    public function run(): void
    {
        // Core system seeders (always run)
        $this->call([
            RolesAndPermissionsSeeder::class,
            LanguageSeeder::class,
            WorkflowSeeder::class,
            MenuSeeder::class,
        ]);

        // Production data (post types + basic categories)
        $this->call(ProductionSeeder::class);

        $this->command->info('Production database seeded successfully!');

        // Seed development data in local environment only
        if (app()->environment('local')) {
            $this->createSampleUsers();
            $this->call(DevelopmentSeeder::class);
            $this->command->info('Development data seeded.');
        }
    }

    /**
     * Create one sample user for each role.
     */
    protected function createSampleUsers(): void
    {
        $password = Hash::make('password');

        $users = [
            ['name' => 'Admin User', 'email' => 'admin@example.com', 'username' => 'admin', 'role' => 'Admin'],
            ['name' => 'Developer User', 'email' => 'developer@example.com', 'username' => 'developer', 'role' => 'Developer'],
            ['name' => 'Editor User', 'email' => 'editor@example.com', 'username' => 'editor', 'role' => 'Editor'],
            ['name' => 'Writer User', 'email' => 'writer@example.com', 'username' => 'writer', 'role' => 'Writer'],
            ['name' => 'Photographer User', 'email' => 'photographer@example.com', 'username' => 'photographer', 'role' => 'Photographer'],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $userData['name'],
                    'username' => $userData['username'],
                    'email_verified_at' => now(),
                    'password' => $password,
                ]
            );
            $user->assignRole($userData['role']);
        }

        $this->command->info('Sample users created (one per role).');
    }
}
