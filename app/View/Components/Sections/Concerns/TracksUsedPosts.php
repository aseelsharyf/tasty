<?php

namespace App\View\Components\Sections\Concerns;

use App\Models\Post;
use App\Services\Layouts\UsedPostTracker;
use Illuminate\Support\Collection;

/**
 * Trait for section components to track which posts they use.
 *
 * This prevents the same post from appearing multiple times on a page
 * when multiple sections fetch posts dynamically.
 */
trait TracksUsedPosts
{
    protected UsedPostTracker $postTracker;

    /**
     * Initialize the post tracker.
     */
    protected function initPostTracker(): void
    {
        $this->postTracker = app(UsedPostTracker::class);
    }

    /**
     * Get IDs that should be excluded from queries (already used by other sections).
     *
     * @param  array<int>  $additionalExcludes  Additional IDs to exclude (e.g., manual posts in this section)
     * @return array<int>
     */
    protected function getExcludeIds(array $additionalExcludes = []): array
    {
        return array_unique(array_merge(
            $this->postTracker->getUsedIds(),
            $additionalExcludes
        ));
    }

    /**
     * Mark a single post as used.
     */
    protected function markPostUsed(?Post $post): void
    {
        if ($post && $post->id) {
            $this->postTracker->markUsed($post->id);
        }
    }

    /**
     * Mark multiple posts as used.
     *
     * @param  Collection<int, Post>|array<Post>  $posts
     */
    protected function markPostsUsed(Collection|array $posts): void
    {
        foreach ($posts as $post) {
            if ($post instanceof Post && $post->id) {
                $this->postTracker->markUsed($post->id);
            }
        }
    }

    /**
     * Mark post IDs as used (for manual slot assignments).
     *
     * @param  array<int>  $ids
     */
    protected function markIdsUsed(array $ids): void
    {
        $this->postTracker->markUsed($ids);
    }
}
