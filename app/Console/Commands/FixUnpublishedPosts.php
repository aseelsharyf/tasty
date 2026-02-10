<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixUnpublishedPosts extends Command
{
    protected $signature = 'app:fix-unpublished-posts';

    protected $description = 'Find draft posts that were previously published and update their status to unpublished';

    public function handle(): int
    {
        $posts = Post::where('status', Post::STATUS_DRAFT)
            ->whereNull('published_at')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('content_versions')
                    ->whereColumn('content_versions.versionable_id', 'posts.id')
                    ->where('content_versions.versionable_type', Post::class)
                    ->where('content_versions.workflow_status', 'published');
            })
            ->get(['id', 'title', 'slug', 'status', 'author_id']);

        if ($posts->isEmpty()) {
            $this->info('No draft posts found that were previously published.');

            return self::SUCCESS;
        }

        $this->info("Found {$posts->count()} post(s) that were previously published but are now drafts:");
        $this->table(
            ['ID', 'Title', 'Slug'],
            $posts->map(fn ($p) => [$p->id, $p->title, $p->slug])->toArray()
        );

        $count = Post::whereIn('id', $posts->pluck('id'))
            ->update(['status' => Post::STATUS_UNPUBLISHED]);

        $this->info("Updated {$count} post(s) to 'unpublished' status.");

        return self::SUCCESS;
    }
}
