<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\WorkflowService;
use Illuminate\Console\Command;

class ProcessScheduledCopydesk extends Command
{
    protected $signature = 'posts:process-scheduled-copydesk';

    protected $description = 'Process posts scheduled for automatic copydesk submission';

    public function handle(WorkflowService $workflowService): int
    {
        $posts = Post::where('scheduled_copydesk_at', '<=', now())
            ->where('workflow_status', 'draft')
            ->whereNotNull('scheduled_copydesk_at')
            ->get();

        if ($posts->isEmpty()) {
            $this->info('No posts scheduled for copydesk submission.');

            return self::SUCCESS;
        }

        $count = 0;
        foreach ($posts as $post) {
            $version = $post->draftVersion;
            if (! $version) {
                $this->warn("Post '{$post->title}' (#{$post->id}) has no draft version, skipping.");

                continue;
            }

            try {
                $workflowService->transition($version, 'copydesk', 'Auto-submitted via scheduled copydesk', $post->author);

                $post->update(['scheduled_copydesk_at' => null]);
                $count++;

                $this->info("Submitted post '{$post->title}' to copydesk.");
            } catch (\Exception $e) {
                $this->error("Failed to submit post '{$post->title}': {$e->getMessage()}");
            }
        }

        $this->info("Processed {$count} posts.");

        return self::SUCCESS;
    }
}
