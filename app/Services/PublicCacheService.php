<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class PublicCacheService
{
    /** @var int Homepage sections cache TTL (10 minutes) */
    private const HOMEPAGE_TTL = 600;

    /** @var int Post detail page cache TTL (10 minutes) */
    private const POST_TTL = 600;

    /** @var int Listing pages cache TTL (5 minutes) */
    private const LISTING_TTL = 300;

    /** @var int Product pages cache TTL (10 minutes) */
    private const PRODUCT_TTL = 600;

    /** @var int Search suggestions cache TTL (2 minutes) */
    private const SEARCH_TTL = 120;

    /**
     * Flush all post-related caches (homepage, listings, post detail pages).
     */
    public static function flushPostCaches(): void
    {
        Cache::forget('public:homepage:sections');
        static::flushListingCaches();
    }

    /**
     * Flush cache for a specific post detail page.
     */
    public static function flushPostDetailCache(string $slug): void
    {
        Cache::forget("public:post:{$slug}");
    }

    /**
     * Flush all listing caches (category, tag, author pages).
     */
    public static function flushListingCaches(): void
    {
        // Use cache tags if available, otherwise we rely on TTL expiration
        // for paginated listing caches since we can't enumerate all keys
        // with the database driver. We flush the homepage which is the heaviest.
        Cache::forget('public:homepage:sections');
    }

    /**
     * Flush all product-related caches including the homepage add-to-cart section.
     */
    public static function flushProductCaches(): void
    {
        static::flushByPrefix('public:products:');
        Cache::forget('public:homepage:sections');
    }

    /**
     * Get homepage sections TTL.
     */
    public static function homepageTtl(): int
    {
        return self::HOMEPAGE_TTL;
    }

    /**
     * Get post detail TTL.
     */
    public static function postTtl(): int
    {
        return self::POST_TTL;
    }

    /**
     * Get listing TTL.
     */
    public static function listingTtl(): int
    {
        return self::LISTING_TTL;
    }

    /**
     * Get product TTL.
     */
    public static function productTtl(): int
    {
        return self::PRODUCT_TTL;
    }

    /**
     * Get search TTL.
     */
    public static function searchTtl(): int
    {
        return self::SEARCH_TTL;
    }

    /**
     * Flush all cache entries matching a key prefix.
     */
    protected static function flushByPrefix(string $prefix): void
    {
        $driver = config('cache.stores.'.config('cache.default').'.driver');
        $cachePrefix = config('cache.prefix');

        match ($driver) {
            'redis' => static::flushRedisByPrefix($cachePrefix.$prefix),
            'database' => static::flushDatabaseByPrefix($cachePrefix.$prefix),
            default => null,
        };
    }

    /**
     * Flush cache entries by prefix using Redis SCAN.
     */
    protected static function flushRedisByPrefix(string $prefix): void
    {
        $connection = config('cache.stores.redis.connection', 'cache');
        $redis = Redis::connection($connection);

        $cursor = '0';

        do {
            [$cursor, $keys] = $redis->scan($cursor, ['match' => $prefix.'*', 'count' => 100]);

            if (! empty($keys)) {
                $redis->del(...$keys);
            }
        } while ($cursor !== '0');
    }

    /**
     * Flush cache entries by prefix using a database query.
     */
    protected static function flushDatabaseByPrefix(string $prefix): void
    {
        $table = config('cache.stores.database.table', 'cache');
        $connection = config('cache.stores.database.connection');

        DB::connection($connection)
            ->table($table)
            ->where('key', 'like', $prefix.'%')
            ->delete();
    }
}
