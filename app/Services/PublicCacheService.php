<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

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
     * Flush all product-related caches.
     */
    public static function flushProductCaches(): void
    {
        Cache::forget('public:products:index');
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
}
