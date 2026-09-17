<?php

namespace App\Observers;

use App\Services\PublicCacheService;
use Illuminate\Database\Eloquent\Model;

class SitemapCacheObserver
{
    public function saved(Model $model): void
    {
        PublicCacheService::flushSitemapCache();
    }

    public function deleted(Model $model): void
    {
        PublicCacheService::flushSitemapCache();
    }

    public function restored(Model $model): void
    {
        PublicCacheService::flushSitemapCache();
    }
}
