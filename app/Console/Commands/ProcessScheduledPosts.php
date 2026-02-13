<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\WorkflowService;
use Illuminate\Console\Command;

class ProcessScheduledPosts extends Command
{
    protected $signature = 'posts:process-scheduled-posts';

    protected $description = 'Auto-publish posts whose scheduled_at time has passed';

    public function handle(WorkflowService $workflowService): int
    {
        $posts = Post::where('status', Post::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->whereNotNull('scheduled_at')
            ->get();

        if ($posts->isEmpty()) {
            $this->info('No scheduled posts ready for publishing.');

            return self::SUCCESS;
        }

        $count = 0;
        foreach ($posts as $post) {
            $version = $post->draftVersion ?? $post->latestVersion;
            if (! $version) {
                $this->warn("Post '{$post->title}' (#{$post->id}) has no version, skipping.");

                continue;
            }

            try {
                // Publish the version directly
                $version->update(['workflow_status' => 'published']);
                $version->activate();

                $post->update([
                    'status' => Post::STATUS_PUBLISHED,
                    'published_at' => now(),
                    'workflow_status' => 'published',
                ]);

                $count++;
                $this->info("Published scheduled post '{$post->title}'.");
            } catch (\Exception $e) {
                $this->error("Failed to publish post '{$post->title}': {$e->getMessage()}");
            }
        }

        $this->info("Published {$count} scheduled posts.");

        return self::SUCCESS;
    }
}
