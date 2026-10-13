<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\ScheduledLayoutAssignment;
use App\Services\WorkflowService;
use Illuminate\Console\Command;

class ProcessScheduledPosts extends Command
{
    protected $signature = 'posts:process-scheduled-posts
                            {--upcoming : Display future scheduled posts without publishing them}';

    protected $description = 'Auto-publish posts whose scheduled_at time has passed';

    public function handle(WorkflowService $workflowService): int
    {
        if ($this->option('upcoming')) {
            return $this->displayUpcomingPosts();
        }

        $posts = Post::where('status', Post::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->whereNotNull('scheduled_at')
            ->with('scheduledLayoutAssignment.contentVersion')
            ->get();

        if ($posts->isEmpty()) {
            $this->info('No scheduled posts ready for publishing.');

            return self::SUCCESS;
        }

        $count = 0;
        foreach ($posts as $post) {
            $assignment = $post->scheduledLayoutAssignment;
            $version = $assignment?->status === ScheduledLayoutAssignment::STATUS_PENDING
                ? $assignment->contentVersion
                : ($post->draftVersion ?? $post->latestVersion);
            if (! $version) {
                $this->warn("Post '{$post->title}' (#{$post->id}) has no version, skipping.");

                continue;
            }

            try {
                $workflowService->publishVersion($version);

                $count++;
                $this->info("Published scheduled post '{$post->title}'.");
            } catch (\Exception $e) {
                $post->scheduledLayoutAssignment()
                    ->pending()
                    ->update(['last_error' => $e->getMessage()]);

                $this->error("Failed to publish post '{$post->title}': {$e->getMessage()}");
            }
        }

        $this->info("Published {$count} scheduled posts.");

        return self::SUCCESS;
    }

    private function displayUpcomingPosts(): int
    {
        $posts = Post::query()
            ->where('status', Post::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>', now())
            ->with('scheduledLayoutAssignment')
            ->orderBy('scheduled_at')
            ->get();

        if ($posts->isEmpty()) {
            $this->info('No upcoming scheduled posts.');

            return self::SUCCESS;
        }

        $timezone = config('cms.timezone');

        $this->info("Upcoming scheduled posts ({$timezone}):");
        $this->table(
            ['ID', 'Title', 'Publishes (MVT)', 'Stored (UTC)', 'Due', 'Slot'],
            $posts->map(function (Post $post) use ($timezone): array {
                $assignment = $post->scheduledLayoutAssignment;
                $slot = $assignment
                    ? "{$assignment->layout_type}:{$assignment->section_id}:{$assignment->slot_index}"
                    : '—';

                return [
                    $post->id,
                    $post->title,
                    $post->scheduled_at->copy()->setTimezone($timezone)->format('d M Y, H:i'),
                    $post->scheduled_at->copy()->utc()->format('d M Y, H:i'),
                    $post->scheduled_at->diffForHumans(),
                    $slot,
                ];
            })->all(),
        );

        return self::SUCCESS;
    }
}
