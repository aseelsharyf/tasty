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
            $query->whereIn('name', ['Admin', 'Editor', 'Writer']);
        })->get();

        if ($authors->isEmpty()) {
            $authors = User::take(3)->get();
        }

        if ($authors->isEmpty()) {
            $this->command->warn('No users found. Skipping PostSeeder.');

            return;
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
                    'workflow_status' => 'approved',
                ])
                ->each(function ($post) use ($categories, $tags) {
                    // Create a version for the post using HasWorkflow trait method
                    $post->createVersion(null, 'Initial version');

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

            // Create 3 draft posts per language (assigned to random writers)
            Post::factory()
                ->count(3)
                ->draft()
                ->article()
                ->withLanguage($language->code)
                ->create([
                    'author_id' => fn () => $authors->random()->id,
                    'workflow_status' => 'draft',
                ])
                ->each(function ($post) {
                    $post->createVersion(null, 'Initial draft');
                });

            // Create 2 pending review posts per language
            Post::factory()
                ->count(2)
                ->pending()
                ->article()
                ->withLanguage($language->code)
                ->create([
                    'author_id' => fn () => $authors->random()->id,
                    'workflow_status' => 'review',
                ])
                ->each(function ($post) {
                    $post->createVersion(null, 'Submitted for review');
                });

            // Create 2 recipes per language
            Post::factory()
                ->count(2)
                ->published()
                ->recipe()
                ->withLanguage($language->code)
                ->create([
                    'author_id' => fn () => $authors->random()->id,
                    'workflow_status' => 'approved',
                ])
                ->each(function ($post) {
                    $post->createVersion(null, 'Initial version');
                });

            $this->command->info("Created posts for language: {$language->name}");
        }
    }
}
