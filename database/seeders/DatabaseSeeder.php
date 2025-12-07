<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database for production.
     *
     * Seeds essential data:
     * - Roles and permissions
     * - Languages (English & Dhivehi)
     * - Default post types
     * - Workflow configuration
     * - One sample user per role
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            LanguageSeeder::class,
            WorkflowSeeder::class,
        ]);

        // Seed default post types
        $this->seedPostTypes();

        // Create one user per role type
        $this->createSampleUsers();

        $this->command->info('Production database seeded successfully!');
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
        // Admin user
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'username' => 'admin',
        ]);

        // Developer user
        User::factory()->developer()->create([
            'name' => 'Developer User',
            'email' => 'developer@example.com',
            'username' => 'developer',
        ]);

        // Editor user
        User::factory()->editor()->create([
            'name' => 'Editor User',
            'email' => 'editor@example.com',
            'username' => 'editor',
        ]);

        // Writer user
        User::factory()->writer()->create([
            'name' => 'Writer User',
            'email' => 'writer@example.com',
            'username' => 'writer',
        ]);

        // Photographer user
        User::factory()->photographer()->create([
            'name' => 'Photographer User',
            'email' => 'photographer@example.com',
            'username' => 'photographer',
        ]);

        $this->command->info('Sample users created (one per role).');
    }
}
