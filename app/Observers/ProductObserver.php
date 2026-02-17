<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\PublicCacheService;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        PublicCacheService::flushProductCaches();
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        PublicCacheService::flushProductCaches();
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        PublicCacheService::flushProductCaches();
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        PublicCacheService::flushProductCaches();
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        PublicCacheService::flushProductCaches();
    }
}
