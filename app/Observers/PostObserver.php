<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\PublicCacheService;

class PostObserver
{
    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post): void
    {
        PublicCacheService::flushPostCaches();
    }

    /**
     * Handle the Post "updated" event.
     */
    public function updated(Post $post): void
    {
        PublicCacheService::flushPostCaches();
        PublicCacheService::flushPostDetailCache($post->slug);

        // Flush old slug cache if slug changed
        if ($post->isDirty('slug') && $post->getOriginal('slug')) {
            PublicCacheService::flushPostDetailCache($post->getOriginal('slug'));
        }
    }

    /**
     * Handle the Post "deleted" event.
     */
    public function deleted(Post $post): void
    {
        PublicCacheService::flushPostCaches();
        PublicCacheService::flushPostDetailCache($post->slug);
    }

    /**
     * Handle the Post "restored" event.
     */
    public function restored(Post $post): void
    {
        PublicCacheService::flushPostCaches();
    }

    /**
     * Handle the Post "force deleted" event.
     */
    public function forceDeleted(Post $post): void
    {
        PublicCacheService::flushPostCaches();
        PublicCacheService::flushPostDetailCache($post->slug);
    }
}
