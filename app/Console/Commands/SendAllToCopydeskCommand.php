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

    protected $description = 'Send all posts to copydesk and optionally regenerate their slugs';

    public function handle(WorkflowService $workflowService): int
    {
        $posts = Post::with(['draftVersion', 'author', 'categories', 'featuredTag'])
            ->get();

        if ($posts->isEmpty()) {
            $this->info('No posts found.');

            return self::SUCCESS;
        }

        $this->info("Found {$posts->count()} post(s).");

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

                if ($dryRun) {
                    $this->line("  [slug] {$oldSlug} → {$newSlug}");
                } else {
                    $post->update(['slug' => $newSlug]);
                }
                $slugsUpdated++;
            }

            // Already in copydesk, skip transition
            if ($post->workflow_status === 'copydesk') {
                $this->line("'{$post->title}' already in copydesk, skipped.");

                continue;
            }

            $version = $post->draftVersion;
            if (! $version) {
                $this->warn("Post '{$post->title}' (#{$post->id}) has no draft version, skipping.");

                continue;
            }

            if ($dryRun) {
                $this->info("[dry-run] Would send '{$post->title}' ({$post->workflow_status}) to copydesk.");
                $sent++;

                continue;
            }

            try {
                // Draft posts can use the normal workflow transition
                if ($post->workflow_status === 'draft') {
                    $workflowService->transition($version, 'copydesk', 'Bulk sent to copydesk via artisan', $admin);
                } else {
                    // For other statuses, force-set to copydesk directly
                    $post->update([
                        'status' => Post::STATUS_DRAFT,
                        'workflow_status' => 'copydesk',
                        'published_at' => null,
                        'scheduled_at' => null,
                    ]);
                    $version->update(['workflow_status' => 'copydesk']);
                }

                $sent++;
                $this->info("Sent '{$post->title}' ({$post->workflow_status}) to copydesk.");
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
