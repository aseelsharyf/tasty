<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostView;
use App\Models\Product;
use App\Models\ProductClick;
use App\Models\ProductView;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnalyticsSeeder extends Seeder
{
    /**
     * Seed analytics data: publish draft posts, then create random views and clicks.
     */
    public function run(): void
    {
        $this->resetSequences();
        $this->publishDraftPosts();
        $this->seedPostViews();
        $this->seedProductViews();
        $this->seedProductClicks();
    }

    /**
     * Reset PostgreSQL sequences to avoid duplicate key errors.
     */
    protected function resetSequences(): void
    {
        $tables = ['post_views', 'product_views', 'product_clicks'];

        foreach ($tables as $table) {
            DB::statement("SELECT setval('{$table}_id_seq', (SELECT COALESCE(MAX(id), 0) + 1 FROM {$table}), false)");
        }
    }

    /**
     * Publish all draft posts that have real content (non-"Untitled").
     */
    protected function publishDraftPosts(): void
    {
        $drafts = Post::where('status', Post::STATUS_DRAFT)
            ->where('title', 'not like', 'Untitled%')
            ->get();

        foreach ($drafts as $post) {
            $post->update([
                'status' => Post::STATUS_PUBLISHED,
                'published_at' => fake()->dateTimeBetween('-90 days', '-1 day'),
                'workflow_status' => 'approved',
            ]);
        }

        $this->command->info("Published {$drafts->count()} draft posts.");
    }

    /**
     * Create random post views spread over the last 90 days.
     */
    protected function seedPostViews(): void
    {
        $posts = Post::where('status', Post::STATUS_PUBLISHED)->get();

        if ($posts->isEmpty()) {
            $this->command->warn('No published posts found — skipping post views.');

            return;
        }

        $total = 0;

        foreach ($posts as $post) {
            // Popular posts get more views
            $viewCount = rand(20, 150);

            $views = [];
            for ($i = 0; $i < $viewCount; $i++) {
                $views[] = [
                    'post_id' => $post->id,
                    'user_id' => null,
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => fake()->userAgent(),
                    'referrer' => fake()->optional(0.5)->url(),
                    'session_id' => fake()->sha256(),
                    'viewed_at' => fake()->dateTimeBetween('-90 days', 'now'),
                ];
            }

            // Extra views today and this week for realistic summary stats
            $todayCount = rand(2, 15);
            for ($i = 0; $i < $todayCount; $i++) {
                $views[] = [
                    'post_id' => $post->id,
                    'user_id' => null,
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => fake()->userAgent(),
                    'referrer' => fake()->optional(0.4)->url(),
                    'session_id' => fake()->sha256(),
                    'viewed_at' => fake()->dateTimeBetween('today', 'now'),
                ];
            }

            // Bulk insert in chunks to avoid memory issues
            foreach (array_chunk($views, 100) as $chunk) {
                PostView::insert($chunk);
            }

            $total += count($views);
        }

        $this->command->info("Created {$total} post views across {$posts->count()} posts.");
    }

    /**
     * Create random product views spread over the last 90 days.
     */
    protected function seedProductViews(): void
    {
        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command->warn('No products found — skipping product views.');

            return;
        }

        $total = 0;

        foreach ($products as $product) {
            $viewCount = rand(15, 80);

            $views = [];
            for ($i = 0; $i < $viewCount; $i++) {
                $views[] = [
                    'product_id' => $product->id,
                    'user_id' => null,
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => fake()->userAgent(),
                    'referrer' => fake()->optional(0.5)->url(),
                    'session_id' => fake()->sha256(),
                    'viewed_at' => fake()->dateTimeBetween('-90 days', 'now'),
                ];
            }

            // Some views today
            $todayCount = rand(1, 8);
            for ($i = 0; $i < $todayCount; $i++) {
                $views[] = [
                    'product_id' => $product->id,
                    'user_id' => null,
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => fake()->userAgent(),
                    'referrer' => fake()->optional(0.4)->url(),
                    'session_id' => fake()->sha256(),
                    'viewed_at' => fake()->dateTimeBetween('today', 'now'),
                ];
            }

            foreach (array_chunk($views, 100) as $chunk) {
                ProductView::insert($chunk);
            }

            $total += count($views);
        }

        $this->command->info("Created {$total} product views across {$products->count()} products.");
    }

    /**
     * Create random product clicks spread over the last 90 days.
     */
    protected function seedProductClicks(): void
    {
        $products = Product::all();

        if ($products->isEmpty()) {
            return;
        }

        $total = 0;

        foreach ($products as $product) {
            $clickCount = rand(5, 30);

            $clicks = [];
            for ($i = 0; $i < $clickCount; $i++) {
                $clicks[] = [
                    'product_id' => $product->id,
                    'user_id' => null,
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => fake()->userAgent(),
                    'referrer' => fake()->optional(0.6)->url(),
                    'session_id' => fake()->sha256(),
                    'clicked_at' => fake()->dateTimeBetween('-90 days', 'now'),
                ];
            }

            ProductClick::insert($clicks);
            $total += $clickCount;
        }

        $this->command->info("Created {$total} product clicks across {$products->count()} products.");
    }
}
