<?php

namespace App\Observers;

use App\Models\Page;

class PageObserver
{
    /**
     * Handle the Page "created" event.
     */
    public function created(Page $page): void
    {
        // No cache to clear for new pages
    }

    /**
     * Handle the Page "updated" event.
     */
    public function updated(Page $page): void
    {
        $this->clearPageCache($page);

        // Also clear old slug cache if slug changed
        if ($page->isDirty('slug') && $page->getOriginal('slug')) {
            Page::clearCache($page->getOriginal('slug'));
        }
    }

    /**
     * Handle the Page "deleted" event.
     */
    public function deleted(Page $page): void
    {
        $this->clearPageCache($page);
    }

    /**
     * Handle the Page "restored" event.
     */
    public function restored(Page $page): void
    {
        $this->clearPageCache($page);
    }

    /**
     * Handle the Page "force deleted" event.
     */
    public function forceDeleted(Page $page): void
    {
        $this->clearPageCache($page);
    }

    /**
     * Clear the cache for the page.
     */
    private function clearPageCache(Page $page): void
    {
        Page::clearCache($page->slug);
    }
}
