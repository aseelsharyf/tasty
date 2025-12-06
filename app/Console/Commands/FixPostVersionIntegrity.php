<?php

namespace App\Console\Commands;

use App\Models\ContentVersion;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FixPostVersionIntegrity extends Command
{
    protected $signature = 'posts:fix-versions {--dry-run : Show what would be fixed without making changes}';

    protected $description = 'Fix posts that have invalid or missing version references';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in dry-run mode. No changes will be made.');
        }

        $posts = Post::withTrashed()->get();
        $fixed = 0;
        $created = 0;

        foreach ($posts as $post) {
            $issues = [];

            // Check if draft_version_id points to a valid version for this post
            if ($post->draft_version_id) {
                $draftVersion = ContentVersion::find($post->draft_version_id);
                if (! $draftVersion ||
                    $draftVersion->versionable_type !== Post::class ||
                    $draftVersion->versionable_id !== $post->id) {
                    $issues[] = "Invalid draft_version_id ({$post->draft_version_id})";
                }
            }

            // Check if active_version_id points to a valid version for this post
            if ($post->active_version_id) {
                $activeVersion = ContentVersion::find($post->active_version_id);
                if (! $activeVersion ||
                    $activeVersion->versionable_type !== Post::class ||
                    $activeVersion->versionable_id !== $post->id) {
                    $issues[] = "Invalid active_version_id ({$post->active_version_id})";
                }
            }

            // Check if post has any versions at all
            $hasVersions = $post->versions()->exists();
            if (! $hasVersions) {
                $issues[] = 'No versions exist for this post';
            }

            if (empty($issues)) {
                continue;
            }

            $this->warn("Post #{$post->id} ({$post->uuid}): ".implode(', ', $issues));

            if ($dryRun) {
                continue;
            }

            // Fix the issues
            if (! $hasVersions) {
                // Create an initial version from the post's current content
                $version = ContentVersion::create([
                    'uuid' => (string) Str::uuid(),
                    'versionable_type' => Post::class,
                    'versionable_id' => $post->id,
                    'version_number' => 1,
                    'content_snapshot' => $post->buildContentSnapshot(),
                    'workflow_status' => $post->status === 'published' ? 'published' : ($post->workflow_status ?? 'draft'),
                    'is_active' => $post->status === 'published',
                    'created_by' => $post->author_id,
                    'version_note' => 'Auto-generated initial version',
                ]);

                $this->info("  Created version #{$version->id} for post #{$post->id}");
                $created++;

                // Update the post's version references
                $updateData = ['draft_version_id' => $version->id];
                if ($post->status === 'published') {
                    $updateData['active_version_id'] = $version->id;
                }
                $post->update($updateData);
            } else {
                // Fix invalid references
                $latestVersion = $post->versions()->latest('version_number')->first();

                $updateData = [];
                if ($post->draft_version_id) {
                    $draftVersion = ContentVersion::find($post->draft_version_id);
                    if (! $draftVersion ||
                        $draftVersion->versionable_type !== Post::class ||
                        $draftVersion->versionable_id !== $post->id) {
                        // Find the actual draft version for this post
                        $actualDraft = $post->versions()->where('workflow_status', 'draft')->latest()->first();
                        $updateData['draft_version_id'] = $actualDraft?->id ?? $latestVersion->id;
                    }
                }

                if ($post->active_version_id) {
                    $activeVersion = ContentVersion::find($post->active_version_id);
                    if (! $activeVersion ||
                        $activeVersion->versionable_type !== Post::class ||
                        $activeVersion->versionable_id !== $post->id) {
                        // Find the actual active version for this post
                        $actualActive = $post->versions()->where('is_active', true)->first();
                        $updateData['active_version_id'] = $actualActive?->id;
                    }
                }

                if (! empty($updateData)) {
                    $post->update($updateData);
                    $this->info("  Fixed references for post #{$post->id}");
                }
            }

            $fixed++;
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("Dry run complete. {$fixed} posts would be fixed.");
        } else {
            $this->info("Fixed {$fixed} posts. Created {$created} new versions.");
        }

        return self::SUCCESS;
    }
}
