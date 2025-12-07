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
                ->article()
                ->withLanguage($language->code)
                ->create([
                    'author_id' => fn () => $authors->random()->id,
                    'workflow_status' => 'draft', // Start as draft
                ])
                ->each(function ($post) use ($categories, $tags) {
                    // Create initial version
                    $version = $post->createVersion(null, 'Initial version');

                    // Attach categories and tags
                    if ($categories->isNotEmpty()) {
                        $post->categories()->attach(
                            $categories->random(min(rand(1, 3), $categories->count()))->pluck('id')
                        );
                    }
                    if ($tags->isNotEmpty()) {
                        $post->tags()->attach(
                            $tags->random(min(rand(2, 5), $tags->count()))->pluck('id')
                        );
                    }

                    // Transition to Published
                    $version->transitionTo('review', 'Ready for review', $post->author_id);
                    $version->transitionTo('approved', 'Approved for publication', $post->author_id);
                    $version->transitionTo('published', 'Published', $post->author_id);

                    // Activate the version
                    $version->activate();
                    $post->publish();
                });

            // Create 3 draft posts per language
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
                    'workflow_status' => 'draft', // Start as draft
                ])
                ->each(function ($post) {
                    $version = $post->createVersion(null, 'Submitted for review');
                    $version->transitionTo('review', 'Submitted for review', $post->author_id);
                    $post->submitForReview();
                });

            // Create 2 approved posts per language
            Post::factory()
                ->count(2)
                ->pending()
                ->article()
                ->withLanguage($language->code)
                ->create([
                    'author_id' => fn () => $authors->random()->id,
                    'workflow_status' => 'draft',
                ])
                ->each(function ($post) {
                    $version = $post->createVersion(null, 'Initial version');
                    $version->transitionTo('review', 'Ready for review', $post->author_id);
                    $version->transitionTo('approved', 'Approved for publication', $post->author_id);
                    // Update post status to pending (or whatever approved maps to, usually pending or draft until published)
                    $post->update(['workflow_status' => 'approved']);
                });

            // Create 2 published recipes per language
            Post::factory()
                ->count(2)
                ->recipe()
                ->withLanguage($language->code)
                ->create([
                    'author_id' => fn () => $authors->random()->id,
                    'workflow_status' => 'draft',
                ])
                ->each(function ($post) {
                    $version = $post->createVersion(null, 'Initial version');

                    $version->transitionTo('review', 'Ready for review', $post->author_id);
                    $version->transitionTo('approved', 'Approved', $post->author_id);
                    $version->transitionTo('published', 'Published', $post->author_id);

                    $version->activate();
                    $post->publish();
                });

            $this->command->info("Created posts for language: {$language->name}");
        }
    }
}
