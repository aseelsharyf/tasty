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
     * - Product categories and products
     */
    public function run(): void
    {
        $this->call([
            TaxonomySeeder::class,
            PageSeeder::class,
            MediaSeeder::class,
            UnitSeeder::class,
            IngredientSeeder::class,
            PostSeeder::class,
            CommentSeeder::class,
            ProductSeeder::class,
            HomepageLayoutSeeder::class,
        ]);

        $this->command->info('Development data seeded successfully!');
    }
}
