<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class RegenerateUntitledSlugs extends Command
{
    protected $signature = 'posts:regenerate-slugs
                            {--dry-run : Show which posts would be updated without making changes}';

    protected $description = 'Regenerate slugs for posts that still have untitled/placeholder slugs';

    public function handle(): int
    {
        $posts = Post::where(function ($query) {
            $query->where('slug', 'LIKE', 'untitled%')
                ->orWhere('slug', 'LIKE', 'post-%')
                ->orWhere('slug', 'post');
        })->get();

        if ($posts->isEmpty()) {
            $this->info('No posts with untitled/placeholder slugs found.');

            return self::SUCCESS;
        }

        $this->info("Found {$posts->count()} post(s) with placeholder slugs.");

        $isDryRun = $this->option('dry-run');
        $updated = 0;

        foreach ($posts as $post) {
            $oldSlug = $post->slug;

            if (empty($post->title) || $post->title === 'Untitled') {
                $this->warn("  Skipping post #{$post->id} — title is empty or still 'Untitled'");

                continue;
            }

            $newSlug = $post->generateUniqueSlugForPost();

            if ($newSlug === $oldSlug) {
                $this->line("  Post #{$post->id} — slug unchanged: {$oldSlug}");

                continue;
            }

            if ($isDryRun) {
                $this->line("  Post #{$post->id}: {$oldSlug} → {$newSlug} (dry run)");
            } else {
                $post->slug = $newSlug;
                $post->saveQuietly();
                $this->line("  Post #{$post->id}: {$oldSlug} → {$newSlug}");
            }

            $updated++;
        }

        $action = $isDryRun ? 'would be updated' : 'updated';
        $this->info("{$updated} post(s) {$action}.");

        return self::SUCCESS;
    }
}
