<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Production seeds (always run):
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
        // Core system seeders
        $this->call([
            RolesAndPermissionsSeeder::class,
            LanguageSeeder::class,
            WorkflowSeeder::class,
            MenuSeeder::class,
        ]);

        // Production data (post types, basic categories, users)
        $this->call(ProductionSeeder::class);

        $this->command->info('Production database seeded successfully!');

        // Seed development data in local environment only
        if (app()->environment('local')) {
            $this->call(DevelopmentSeeder::class);
            $this->command->info('Development data seeded.');
        }
    }
}
