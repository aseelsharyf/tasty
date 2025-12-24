<?php

namespace App\Providers;

use App\Services\Layouts\HomepageConfigurationService;
use App\Services\Layouts\SectionRegistry;
use Illuminate\Support\ServiceProvider;

class LayoutServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SectionRegistry::class, function () {
            return new SectionRegistry;
        });

        $this->app->singleton(HomepageConfigurationService::class, function ($app) {
            return new HomepageConfigurationService(
                $app->make(SectionRegistry::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
