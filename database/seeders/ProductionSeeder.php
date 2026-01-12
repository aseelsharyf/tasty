<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProductionSeeder extends Seeder
{
    /**
     * Seed essential production data.
     *
     * This seeder creates:
     * - Default post types
     * - Basic categories structure
     * - Sample users (one per role)
     */
    public function run(): void
    {
        $this->seedPostTypes();
        $this->seedCategories();
        $this->seedUsers();
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

    /**
     * Seed sample users (one per role).
     */
    protected function seedUsers(): void
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

        $this->command->info('Sample users seeded (one per role).');
    }
}
