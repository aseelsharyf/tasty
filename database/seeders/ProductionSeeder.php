<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Seed essential production data.
     *
     * This seeder creates:
     * - Default post types
     * - Basic categories structure
     */
    public function run(): void
    {
        $this->seedPostTypes();
        $this->seedCategories();
    }

    /**
     * Seed default post types configuration.
     */
    protected function seedPostTypes(): void
    {
        Setting::setPostTypes(Setting::getDefaultPostTypes());
        $this->command->info('Default post types seeded.');
    }

    /**
     * Seed basic categories for production.
     */
    protected function seedCategories(): void
    {
        $categories = [
            [
                'name' => ['en' => 'Feature', 'dv' => 'ފީޗަރ'],
                'slug' => 'feature',
                'description' => ['en' => 'Featured stories and highlights'],
            ],
            [
                'name' => ['en' => 'People', 'dv' => 'މީހުން'],
                'slug' => 'people',
                'description' => ['en' => 'Stories about chefs and food personalities'],
            ],
            [
                'name' => ['en' => 'Review', 'dv' => 'ރިވިއު'],
                'slug' => 'review',
                'description' => ['en' => 'Restaurant and food reviews'],
            ],
            [
                'name' => ['en' => 'Recipe', 'dv' => 'ރެސިޕީ'],
                'slug' => 'recipe',
                'description' => ['en' => 'Recipes and cooking guides'],
            ],
            [
                'name' => ['en' => 'News', 'dv' => 'ޚަބަރު'],
                'slug' => 'news',
                'description' => ['en' => 'Food industry news and updates'],
            ],
            [
                'name' => ['en' => 'Travel', 'dv' => 'ދަތުރު'],
                'slug' => 'travel',
                'description' => ['en' => 'Food-focused travel guides'],
            ],
        ];

        $order = 1;
        foreach ($categories as $categoryData) {
            Category::firstOrCreate(
                ['slug' => $categoryData['slug']],
                [
                    ...$categoryData,
                    'order' => $order++,
                ]
            );
        }

        $this->command->info('Basic categories seeded.');
    }
}
