<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\User;
use App\Services\WorkflowService;
use Illuminate\Console\Command;

class SendAllToCopydeskCommand extends Command
{
    protected $signature = 'posts:send-all-to-copydesk
                            {--regenerate-slugs : Regenerate slugs from current titles}
                            {--dry-run : Preview changes without applying them}';

    protected $description = 'Send all draft posts to copydesk and optionally regenerate their slugs';

    public function handle(WorkflowService $workflowService): int
    {
        $posts = Post::where('workflow_status', 'draft')
            ->with(['draftVersion', 'author', 'categories', 'featuredTag'])
            ->get();

        if ($posts->isEmpty()) {
            $this->info('No draft posts found.');

            return self::SUCCESS;
        }

        $this->info("Found {$posts->count()} draft post(s).");

        $admin = User::role('Admin')->first();
        if (! $admin) {
            $this->error('No Admin user found to perform the transitions.');

            return self::FAILURE;
        }

        $regenerateSlugs = $this->option('regenerate-slugs');
        $dryRun = $this->option('dry-run');
        $sent = 0;
        $slugsUpdated = 0;

        foreach ($posts as $post) {
            $oldSlug = $post->slug;

            // Regenerate slug if requested
            if ($regenerateSlugs) {
                $newSlug = $post->generateUniqueSlugForPost();

                if ($oldSlug !== $newSlug) {
                    if ($dryRun) {
                        $this->line("  [slug] {$oldSlug} → {$newSlug}");
                    } else {
                        $post->update(['slug' => $newSlug]);
                    }
                    $slugsUpdated++;
                }
            }

            // Send to copydesk
            $version = $post->draftVersion;
            if (! $version) {
                $this->warn("Post '{$post->title}' (#{$post->id}) has no draft version, skipping.");

                continue;
            }

            if ($dryRun) {
                $this->info("[dry-run] Would send '{$post->title}' to copydesk.");
                $sent++;

                continue;
            }

            try {
                $workflowService->transition($version, 'copydesk', 'Bulk sent to copydesk via artisan', $admin);
                $sent++;
                $this->info("Sent '{$post->title}' to copydesk.");
            } catch (\Exception $e) {
                $this->error("Failed to send '{$post->title}': {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Sent {$sent} post(s) to copydesk.");

        if ($regenerateSlugs) {
            $this->info("Regenerated {$slugsUpdated} slug(s).");
        }

        return self::SUCCESS;
    }
}
