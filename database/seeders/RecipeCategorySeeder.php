<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class RecipeCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find the existing "Recipe" parent category (slug: recipe)
        $recipesParent = Category::where('slug', 'recipe')->first();

        if (! $recipesParent) {
            $this->command->error('Recipe parent category not found. Please create it first.');

            return;
        }

        $recipeCategories = [
            ['name' => ['en' => 'Breakfast', 'dv' => 'ހެނދުނުގެ ނާސްތާ'], 'slug' => 'recipe-breakfast', 'order' => 1],
            ['name' => ['en' => 'Lunch', 'dv' => 'މެންދުރުގެ ކެއުން'], 'slug' => 'recipe-lunch', 'order' => 2],
            ['name' => ['en' => 'Dinner', 'dv' => 'ރޭގަނޑުގެ ކެއުން'], 'slug' => 'recipe-dinner', 'order' => 3],
            ['name' => ['en' => 'Snacks', 'dv' => 'ލުއި ކާނާ'], 'slug' => 'recipe-snacks', 'order' => 4],
            ['name' => ['en' => 'Desserts', 'dv' => 'ފޮނި ކާނާ'], 'slug' => 'recipe-desserts', 'order' => 5],
            ['name' => ['en' => 'Drinks', 'dv' => 'ބުއިންތައް'], 'slug' => 'recipe-drinks', 'order' => 6],
            ['name' => ['en' => 'Seafood', 'dv' => 'ކަނޑުމަހުގެ ކެއުން'], 'slug' => 'recipe-seafood', 'order' => 7],
            ['name' => ['en' => 'Vegetarian', 'dv' => 'ތަރުކާރީ ކެއުން'], 'slug' => 'recipe-vegetarian', 'order' => 8],
            ['name' => ['en' => 'Traditional', 'dv' => 'ދިވެހި އާދަކާދަ'], 'slug' => 'recipe-traditional', 'order' => 9],
            ['name' => ['en' => 'Quick & Easy', 'dv' => 'ފަސޭހަ ކެއުން'], 'slug' => 'recipe-quick-easy', 'order' => 10],
            ['name' => ['en' => 'Healthy', 'dv' => 'ސިއްހީ ކެއުން'], 'slug' => 'recipe-healthy', 'order' => 11],
            ['name' => ['en' => 'Special Occasions', 'dv' => 'ހާއްސަ މުނާސަބާ'], 'slug' => 'recipe-special-occasions', 'order' => 12],
        ];

        foreach ($recipeCategories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'parent_id' => $recipesParent->id,
                    'order' => $category['order'],
                ]
            );
        }

        $this->command->info('Recipe categories seeded successfully under "Recipe" parent.');
    }
}
