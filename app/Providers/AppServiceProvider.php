<?php

namespace App\Providers;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Observers\MenuItemObserver;
use App\Observers\MenuObserver;
use App\Observers\PageObserver;
use App\Services\Layouts\UsedPostTracker;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Request-scoped service to track used posts across sections
        // Prevents the same post from appearing multiple times on a page
        $this->app->scoped(UsedPostTracker::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Menu::observe(MenuObserver::class);
        MenuItem::observe(MenuItemObserver::class);
        Page::observe(PageObserver::class);
    }
}
