<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Post;
use App\Services\OgImageService;
use Illuminate\Console\Command;

class GenerateOgImages extends Command
{
    protected $signature = 'og:generate
                            {--post= : Generate for a specific post by slug}
                            {--all : Generate for all published posts}
                            {--category= : Generate for a specific category by slug}
                            {--categories : Generate for all categories}
                            {--default : Generate the default site OG image}
                            {--force : Regenerate even if image exists}';

    protected $description = 'Generate OG images for posts, categories, and default pages';

    public function handle(OgImageService $ogImageService): int
    {
        $force = $this->option('force');

        // Generate default OG image
        if ($this->option('default')) {
            $this->info('Generating default OG image...');
            $url = $ogImageService->generateDefault(force: $force);

            if ($url) {
                $this->info("Generated: {$url}");
            } else {
                $this->warn('Failed to generate default OG image');
            }

            return self::SUCCESS;
        }

        // Generate for specific post
        if ($this->option('post')) {
            $post = Post::where('slug', $this->option('post'))->first();

            if (! $post) {
                $this->error('Post not found: '.$this->option('post'));

                return self::FAILURE;
            }

            $this->generateForPost($ogImageService, $post);

            return self::SUCCESS;
        }

        // Generate for specific category
        if ($this->option('category')) {
            $category = Category::where('slug', $this->option('category'))->first();

            if (! $category) {
                $this->error('Category not found: '.$this->option('category'));

                return self::FAILURE;
            }

            $this->generateForCategory($ogImageService, $category);

            return self::SUCCESS;
        }

        // Generate for all categories
        if ($this->option('categories')) {
            $categories = Category::all();

            $this->info("Generating OG images for {$categories->count()} categories...");

            $bar = $this->output->createProgressBar($categories->count());
            $bar->start();

            foreach ($categories as $category) {
                if ($force) {
                    $ogImageService->deleteForCategory($category);
                }

                $ogImageService->generateForCategory($category);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info('Done!');

            return self::SUCCESS;
        }

        // Generate for all posts
        if ($this->option('all')) {
            $posts = Post::published()
                ->whereNotNull('featured_media_id')
                ->get();

            $this->info("Generating OG images for {$posts->count()} posts...");

            $bar = $this->output->createProgressBar($posts->count());
            $bar->start();

            foreach ($posts as $post) {
                if ($force) {
                    $ogImageService->deleteForPost($post);
                }

                $ogImageService->generateForPost($post);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info('Done!');

            return self::SUCCESS;
        }

        $this->info('Usage:');
        $this->line('  php artisan og:generate --post=my-post-slug');
        $this->line('  php artisan og:generate --all');
        $this->line('  php artisan og:generate --all --force');
        $this->line('  php artisan og:generate --category=my-category-slug');
        $this->line('  php artisan og:generate --categories');
        $this->line('  php artisan og:generate --categories --force');
        $this->line('  php artisan og:generate --default');
        $this->line('  php artisan og:generate --default --force');

        return self::SUCCESS;
    }

    protected function generateForPost(OgImageService $ogImageService, Post $post): void
    {
        $this->info("Generating OG image for: {$post->title}");

        if ($this->option('force')) {
            $ogImageService->deleteForPost($post);
        }

        $url = $ogImageService->generateForPost($post);

        if ($url) {
            $this->info("Generated: {$url}");
        } else {
            $this->warn('Failed to generate OG image (no featured image?)');
        }
    }

    protected function generateForCategory(OgImageService $ogImageService, Category $category): void
    {
        $this->info("Generating OG image for category: {$category->name}");

        if ($this->option('force')) {
            $ogImageService->deleteForCategory($category);
        }

        $url = $ogImageService->generateForCategory($category);

        if ($url) {
            $this->info("Generated: {$url}");
        } else {
            $this->warn('Failed to generate OG image for category');
        }
    }
}
