<?php

namespace App\Observers;

use App\Models\Menu;
use App\Services\MenuService;

class MenuObserver
{
    public function __construct(
        private MenuService $menuService
    ) {}

    /**
     * Handle the Menu "created" event.
     */
    public function created(Menu $menu): void
    {
        $this->clearMenuCache($menu);
    }

    /**
     * Handle the Menu "updated" event.
     */
    public function updated(Menu $menu): void
    {
        $this->clearMenuCache($menu);

        // Also clear old location cache if location changed
        if ($menu->isDirty('location') && $menu->getOriginal('location')) {
            $this->menuService->clearLocationCache($menu->getOriginal('location'));
        }
    }

    /**
     * Handle the Menu "deleted" event.
     */
    public function deleted(Menu $menu): void
    {
        $this->clearMenuCache($menu);
    }

    /**
     * Handle the Menu "restored" event.
     */
    public function restored(Menu $menu): void
    {
        $this->clearMenuCache($menu);
    }

    /**
     * Handle the Menu "force deleted" event.
     */
    public function forceDeleted(Menu $menu): void
    {
        $this->clearMenuCache($menu);
    }

    /**
     * Clear the cache for the menu's location.
     */
    private function clearMenuCache(Menu $menu): void
    {
        if ($menu->location) {
            $this->menuService->clearLocationCache($menu->location);
        }
    }
}
