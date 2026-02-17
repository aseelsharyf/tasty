<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostView;
use App\Models\ProductClick;
use App\Models\ProductView;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get full article analytics for a given period.
     *
     * @return array<string, mixed>
     */
    public function getArticleAnalytics(string $period = '30d'): array
    {
        [$from, $to] = $this->parsePeriod($period);

        return [
            'summary' => $this->getArticleSummaryStats(),
            'top_articles' => $this->getTopArticles($from, $to, 10),
            'views_over_time' => $this->getArticleViewsOverTime($period),
            'views_by_type' => $this->getArticleViewsByType($from, $to),
            'views_by_category' => $this->getArticleViewsByCategory($from, $to),
        ];
    }

    /**
     * Summary stats: views today, this week, this month.
     *
     * @return array{today: int, this_week: int, this_month: int, total: int}
     */
    public function getArticleSummaryStats(): array
    {
        return [
            'today' => PostView::whereDate('viewed_at', today())->count(),
            'this_week' => PostView::where('viewed_at', '>=', now()->startOfWeek())->count(),
            'this_month' => PostView::where('viewed_at', '>=', now()->startOfMonth())->count(),
            'total' => PostView::count(),
        ];
    }

    /**
     * Top articles by view count within a date range.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTopArticles(Carbon $from, Carbon $to, int $limit = 10): array
    {
        return PostView::select('post_id', DB::raw('count(*) as views_count'))
            ->whereBetween('viewed_at', [$from, $to])
            ->groupBy('post_id')
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $post = Post::withTrashed()->with('author', 'categories')->find($row->post_id);

                return [
                    'id' => $row->post_id,
                    'uuid' => $post?->uuid,
                    'title' => $post?->title ?? 'Deleted Post',
                    'slug' => $post?->slug,
                    'post_type' => $post?->post_type,
                    'author' => $post?->author ? [
                        'id' => $post->author->id,
                        'name' => $post->author->name,
                        'avatar_url' => $post->author->avatar_url,
                    ] : null,
                    'category' => $post?->categories->first()?->name,
                    'views' => $row->views_count,
                    'language_code' => $post?->language_code,
                ];
            })
            ->toArray();
    }

    /**
     * Daily view counts for the chart.
     *
     * @return array<int, array{date: string, count: int}>
     */
    public function getArticleViewsOverTime(string $period = '30d'): array
    {
        $days = $this->periodToDays($period);
        $startDate = now()->subDays($days)->startOfDay();

        $views = PostView::where('viewed_at', '>=', $startDate)
            ->select(DB::raw('DATE(viewed_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $result = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $result[] = [
                'date' => $date,
                'count' => $views->get($date)?->count ?? 0,
            ];
        }

        return $result;
    }

    /**
     * Views grouped by post type.
     *
     * @return array<int, array{type: string, views: int}>
     */
    public function getArticleViewsByType(Carbon $from, Carbon $to): array
    {
        return PostView::join('posts', 'post_views.post_id', '=', 'posts.id')
            ->whereBetween('post_views.viewed_at', [$from, $to])
            ->select('posts.post_type', DB::raw('count(*) as views'))
            ->groupBy('posts.post_type')
            ->orderByDesc('views')
            ->get()
            ->map(fn ($row) => [
                'type' => $row->post_type ?? 'article',
                'views' => $row->views,
            ])
            ->toArray();
    }

    /**
     * Views grouped by category.
     *
     * @return array<int, array{category: string, views: int}>
     */
    public function getArticleViewsByCategory(Carbon $from, Carbon $to): array
    {
        $locale = app()->getLocale();

        return PostView::join('posts', 'post_views.post_id', '=', 'posts.id')
            ->join('category_post', 'posts.id', '=', 'category_post.post_id')
            ->join('categories', 'category_post.category_id', '=', 'categories.id')
            ->whereBetween('post_views.viewed_at', [$from, $to])
            ->select(DB::raw("categories.name->>'{$locale}' as category"), DB::raw('count(*) as views'))
            ->groupBy(DB::raw("categories.name->>'{$locale}'"))
            ->orderByDesc('views')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'views' => $row->views,
            ])
            ->toArray();
    }

    /**
     * Get full author analytics for a given period.
     *
     * @return array<string, mixed>
     */
    public function getAuthorAnalytics(string $period = '30d'): array
    {
        [$from, $to] = $this->parsePeriod($period);

        return [
            'leaderboard' => $this->getAuthorLeaderboard($from, $to, 15),
            'publishing_trend' => $this->getAuthorPublishingTrend($period),
        ];
    }

    /**
     * Author leaderboard: name, avatar, published count, total views, avg views.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAuthorLeaderboard(Carbon $from, Carbon $to, int $limit = 15): array
    {
        // Get authors with published counts in the period
        $authors = Post::where('status', Post::STATUS_PUBLISHED)
            ->whereBetween('published_at', [$from, $to])
            ->select('author_id', DB::raw('count(*) as published_count'))
            ->groupBy('author_id')
            ->orderByDesc('published_count')
            ->limit($limit)
            ->get();

        return $authors->map(function ($row) use ($from, $to) {
            $user = \App\Models\User::find($row->author_id);
            if (! $user) {
                return null;
            }

            // Get views for this author's posts in the period
            $totalViews = PostView::whereIn('post_id', function ($query) use ($row) {
                $query->select('id')
                    ->from('posts')
                    ->where('author_id', $row->author_id);
            })
                ->whereBetween('viewed_at', [$from, $to])
                ->count();

            return [
                'user' => [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url,
                ],
                'published_count' => $row->published_count,
                'total_views' => $totalViews,
                'avg_views' => $row->published_count > 0 ? round($totalViews / $row->published_count) : 0,
            ];
        })
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Posts published per day (publishing trend).
     *
     * @return array<int, array{date: string, count: int}>
     */
    public function getAuthorPublishingTrend(string $period = '30d'): array
    {
        $days = $this->periodToDays($period);
        $startDate = now()->subDays($days)->startOfDay();

        $posts = Post::where('status', Post::STATUS_PUBLISHED)
            ->where('published_at', '>=', $startDate)
            ->select(DB::raw('DATE(published_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $result = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $result[] = [
                'date' => $date,
                'count' => $posts->get($date)?->count ?? 0,
            ];
        }

        return $result;
    }

    /**
     * Get full product analytics for a given period.
     *
     * @return array<string, mixed>
     */
    public function getProductAnalytics(string $period = '30d'): array
    {
        [$from, $to] = $this->parsePeriod($period);

        return [
            'summary' => $this->getProductSummaryStats($from, $to),
            'top_by_views' => $this->getTopProductsByViews($from, $to, 10),
            'top_by_clicks' => $this->getTopProductsByClicks($from, $to, 10),
            'over_time' => $this->getProductViewsAndClicksOverTime($period),
            'by_store' => $this->getProductsByStore($from, $to),
            'by_category' => $this->getProductsByCategory($from, $to),
        ];
    }

    /**
     * Product summary: total views, total clicks, CTR.
     *
     * @return array{views: int, clicks: int, ctr: float}
     */
    public function getProductSummaryStats(Carbon $from, Carbon $to): array
    {
        $views = ProductView::whereBetween('viewed_at', [$from, $to])->count();
        $clicks = ProductClick::whereBetween('clicked_at', [$from, $to])->count();

        return [
            'views' => $views,
            'clicks' => $clicks,
            'ctr' => $views > 0 ? round(($clicks / $views) * 100, 1) : 0,
        ];
    }

    /**
     * Top products by view count.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTopProductsByViews(Carbon $from, Carbon $to, int $limit = 10): array
    {
        return ProductView::select('product_id', DB::raw('count(*) as views_count'))
            ->whereBetween('viewed_at', [$from, $to])
            ->groupBy('product_id')
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get()
            ->map(function ($row) use ($from, $to) {
                $product = \App\Models\Product::withTrashed()->with('category', 'store')->find($row->product_id);
                $clicks = ProductClick::where('product_id', $row->product_id)
                    ->whereBetween('clicked_at', [$from, $to])
                    ->count();

                return [
                    'id' => $row->product_id,
                    'uuid' => $product?->uuid,
                    'title' => $product?->title ?? 'Deleted Product',
                    'category' => $product?->category?->name,
                    'store' => $product?->store?->name,
                    'views' => $row->views_count,
                    'clicks' => $clicks,
                    'ctr' => $row->views_count > 0 ? round(($clicks / $row->views_count) * 100, 1) : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Top products by click count.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTopProductsByClicks(Carbon $from, Carbon $to, int $limit = 10): array
    {
        return ProductClick::select('product_id', DB::raw('count(*) as clicks_count'))
            ->whereBetween('clicked_at', [$from, $to])
            ->groupBy('product_id')
            ->orderByDesc('clicks_count')
            ->limit($limit)
            ->get()
            ->map(function ($row) use ($from, $to) {
                $product = \App\Models\Product::withTrashed()->with('category', 'store')->find($row->product_id);
                $views = ProductView::where('product_id', $row->product_id)
                    ->whereBetween('viewed_at', [$from, $to])
                    ->count();

                return [
                    'id' => $row->product_id,
                    'uuid' => $product?->uuid,
                    'title' => $product?->title ?? 'Deleted Product',
                    'category' => $product?->category?->name,
                    'store' => $product?->store?->name,
                    'clicks' => $row->clicks_count,
                    'views' => $views,
                    'ctr' => $views > 0 ? round(($row->clicks_count / $views) * 100, 1) : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Dual time-series: views + clicks per day.
     *
     * @return array<int, array{date: string, views: int, clicks: int}>
     */
    public function getProductViewsAndClicksOverTime(string $period = '30d'): array
    {
        $days = $this->periodToDays($period);
        $startDate = now()->subDays($days)->startOfDay();

        $views = ProductView::where('viewed_at', '>=', $startDate)
            ->select(DB::raw('DATE(viewed_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $clicks = ProductClick::where('clicked_at', '>=', $startDate)
            ->select(DB::raw('DATE(clicked_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $result = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $result[] = [
                'date' => $date,
                'views' => $views->get($date)?->count ?? 0,
                'clicks' => $clicks->get($date)?->count ?? 0,
            ];
        }

        return $result;
    }

    /**
     * Product views grouped by store.
     *
     * @return array<int, array{store: string, views: int}>
     */
    public function getProductsByStore(Carbon $from, Carbon $to): array
    {
        return ProductView::join('products', 'product_views.product_id', '=', 'products.id')
            ->join('product_stores', 'products.product_store_id', '=', 'product_stores.id')
            ->whereBetween('product_views.viewed_at', [$from, $to])
            ->select('product_stores.name as store', DB::raw('count(*) as views'))
            ->groupBy('product_stores.name')
            ->orderByDesc('views')
            ->get()
            ->map(fn ($row) => [
                'store' => $row->store,
                'views' => $row->views,
            ])
            ->toArray();
    }

    /**
     * Product views grouped by category.
     *
     * @return array<int, array{category: string, views: int}>
     */
    public function getProductsByCategory(Carbon $from, Carbon $to): array
    {
        $locale = app()->getLocale();

        return ProductView::join('products', 'product_views.product_id', '=', 'products.id')
            ->join('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->whereBetween('product_views.viewed_at', [$from, $to])
            ->select(DB::raw("product_categories.name->>'{$locale}' as category"), DB::raw('count(*) as views'))
            ->groupBy(DB::raw("product_categories.name->>'{$locale}'"))
            ->orderByDesc('views')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'views' => $row->views,
            ])
            ->toArray();
    }

    /**
     * Parse period string into [from, to] Carbon instances.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function parsePeriod(string $period): array
    {
        $days = $this->periodToDays($period);

        return [
            now()->subDays($days)->startOfDay(),
            now()->endOfDay(),
        ];
    }

    protected function periodToDays(string $period): int
    {
        return match ($period) {
            '7d' => 7,
            '90d' => 90,
            default => 30,
        };
    }
}
