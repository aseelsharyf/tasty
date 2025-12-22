<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    /**
     * Seed additional development data.
     *
     * This seeder adds sample content for local development:
     * - Categories and tags
     * - Pages (home, about, contact, etc.)
     * - Posts with versions and workflow states
     * - Comments on posts
     */
    public function run(): void
    {
        $this->call([
            TaxonomySeeder::class,
            PageSeeder::class,
            MediaSeeder::class,
            PostSeeder::class,
            CommentSeeder::class,
        ]);

        $this->command->info('Development data seeded successfully!');
    }
}
