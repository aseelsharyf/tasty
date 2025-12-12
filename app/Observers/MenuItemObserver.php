<?php

namespace App\Observers;

use App\Models\MenuItem;
use App\Services\MenuService;

class MenuItemObserver
{
    public function __construct(
        private MenuService $menuService
    ) {}

    /**
     * Handle the MenuItem "created" event.
     */
    public function created(MenuItem $menuItem): void
    {
        $this->clearMenuCache($menuItem);
    }

    /**
     * Handle the MenuItem "updated" event.
     */
    public function updated(MenuItem $menuItem): void
    {
        $this->clearMenuCache($menuItem);
    }

    /**
     * Handle the MenuItem "deleted" event.
     */
    public function deleted(MenuItem $menuItem): void
    {
        $this->clearMenuCache($menuItem);
    }

    /**
     * Handle the MenuItem "restored" event.
     */
    public function restored(MenuItem $menuItem): void
    {
        $this->clearMenuCache($menuItem);
    }

    /**
     * Handle the MenuItem "force deleted" event.
     */
    public function forceDeleted(MenuItem $menuItem): void
    {
        $this->clearMenuCache($menuItem);
    }

    /**
     * Clear the cache for the menu item's parent menu location.
     */
    private function clearMenuCache(MenuItem $menuItem): void
    {
        $menu = $menuItem->menu;
        if ($menu && $menu->location) {
            $this->menuService->clearLocationCache($menu->location);
        }
    }
}
