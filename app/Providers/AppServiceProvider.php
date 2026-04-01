<?php

namespace App\Providers;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Observers\MenuItemObserver;
use App\Observers\MenuObserver;
use App\Observers\PageObserver;
use App\Observers\PostObserver;
use App\Observers\ProductObserver;
use App\Services\Layouts\UsedPostTracker;
use App\Services\TelegramService;
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

        $this->app->singleton(TelegramService::class, fn () => new TelegramService(
            botToken: config('services.telegram.bot_token', ''),
            chatId: config('services.telegram.chat_id', ''),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Menu::observe(MenuObserver::class);
        MenuItem::observe(MenuItemObserver::class);
        Page::observe(PageObserver::class);
        Post::observe(PostObserver::class);
        Product::observe(ProductObserver::class);
    }
}
