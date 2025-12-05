<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Language;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing writers/editors or create them
        $authors = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'editor', 'writer']);
        })->get();

        if ($authors->isEmpty()) {
            $authors = User::take(3)->get();
        }

        // Get languages
        $languages = Language::active()->get();
        if ($languages->isEmpty()) {
            $this->command->warn('No active languages found. Skipping PostSeeder.');

            return;
        }

        // Get categories and tags
        $categories = Category::all();
        $tags = Tag::all();

        foreach ($languages as $language) {
            // Create 10 published articles per language
            Post::factory()
                ->count(10)
                ->published()
                ->article()
                ->withLanguage($language->code)
                ->create([
                    'author_id' => fn () => $authors->random()->id,
                ])
                ->each(function ($post) use ($categories, $tags) {
                    // Attach 1-3 random categories
                    if ($categories->isNotEmpty()) {
                        $post->categories()->attach(
                            $categories->random(min(rand(1, 3), $categories->count()))->pluck('id')
                        );
                    }
                    // Attach 2-5 random tags
                    if ($tags->isNotEmpty()) {
                        $post->tags()->attach(
                            $tags->random(min(rand(2, 5), $tags->count()))->pluck('id')
                        );
                    }
                });

            // Create 3 draft posts per language
            Post::factory()
                ->count(3)
                ->draft()
                ->article()
                ->withLanguage($language->code)
                ->create([
                    'author_id' => fn () => $authors->random()->id,
                ]);

            // Create 2 recipes per language
            Post::factory()
                ->count(2)
                ->published()
                ->recipe()
                ->withLanguage($language->code)
                ->create([
                    'author_id' => fn () => $authors->random()->id,
                ]);

            $this->command->info("Created posts for language: {$language->name}");
        }
    }
}
