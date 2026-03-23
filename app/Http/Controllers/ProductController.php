<?php

namespace App\Http\Controllers;

use App\Jobs\RecordViewJob;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductClick;
use App\Models\ProductStore;
use App\Services\AnalyticsService;
use App\Services\PublicCacheService;
use App\Services\SeoService;
use App\Support\BotDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(
        protected SeoService $seo
    ) {}

    /**
     * Display all products with category filters.
     */
    public function index(): Response
    {
        $page = request()->integer('page', 1);

        $html = PublicCacheService::remember("public:products:index:page:{$page}", PublicCacheService::productTtl(), function () {
            $this->seo->setProductsIndex();

            $categories = ProductCategory::query()
                ->active()
                ->ordered()
                ->get();

            $products = Product::query()
                ->active()
                ->orderByRaw("CASE WHEN product_type = 'in_house' THEN 0 ELSE 1 END")
                ->ordered()
                ->with(['featuredMedia', 'tags', 'category', 'store', 'variants'])
                ->paginate(12);

            return view('products.index', [
                'categories' => $categories,
                'products' => $products,
                'currentCategory' => null,
            ])->render();
        });

        return new Response($html);
    }

    /**
     * Display all products from a specific store with pagination.
     */
    public function byStore(ProductStore $store): Response
    {
        abort_unless($store->is_active, 404);

        $page = request()->integer('page', 1);
        $cacheKey = "public:products:store:{$store->slug}:page:{$page}";

        $html = PublicCacheService::remember($cacheKey, PublicCacheService::productTtl(), function () use ($store) {
            $this->seo->setProductStore($store);

            $products = $store->products()
                ->active()
                ->orderByRaw("CASE WHEN product_type = 'in_house' THEN 0 ELSE 1 END")
                ->ordered()
                ->with(['featuredMedia', 'tags', 'category', 'store', 'variants'])
                ->paginate(12);

            return view('products.store', [
                'store' => $store,
                'products' => $products,
            ])->render();
        });

        return new Response($html);
    }

    /**
     * Load more products for a store (AJAX).
     */
    public function loadMore(Request $request, ProductStore $store): JsonResponse
    {
        $page = $request->input('page', 1);
        $perPage = 6;

        $products = $store->products()
            ->active()
            ->ordered()
            ->with(['featuredMedia', 'tags', 'category', 'store', 'variants'])
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $totalProducts = $store->products()->active()->count();
        $hasMore = ($page * $perPage) < $totalProducts;

        $html = '';
        foreach ($products as $product) {
            $html .= view('components.cards.product', [
                'product' => $product,
                'showPrice' => true,
            ])->render();
        }

        return response()->json([
            'html' => $html,
            'hasMore' => $hasMore,
            'nextPage' => $page + 1,
        ]);
    }

    /**
     * Display products for a specific category.
     */
    public function byCategory(ProductCategory $category): Response
    {
        abort_unless($category->is_active, 404);

        $page = request()->integer('page', 1);
        $cacheKey = "public:products:category:{$category->slug}:page:{$page}";

        $html = PublicCacheService::remember($cacheKey, PublicCacheService::productTtl(), function () use ($category) {
            $this->seo->setProductCategory($category);

            $categories = ProductCategory::query()
                ->active()
                ->ordered()
                ->get();

            $products = $category->products()
                ->active()
                ->orderByRaw("CASE WHEN product_type = 'in_house' THEN 0 ELSE 1 END")
                ->ordered()
                ->with(['featuredMedia', 'tags', 'category', 'store', 'variants'])
                ->paginate(12);

            return view('products.category', [
                'categories' => $categories,
                'products' => $products,
                'currentCategory' => $category,
            ])->render();
        });

        return new Response($html);
    }

    /**
     * Display products for a specific tag.
     */
    public function byTag(\App\Models\Tag $tag): Response
    {
        $page = request()->integer('page', 1);
        $cacheKey = "public:products:tag:{$tag->slug}:page:{$page}";

        $html = PublicCacheService::remember($cacheKey, PublicCacheService::productTtl(), function () use ($tag) {
            $this->seo->setBasic($tag->name.' Products', "Browse products tagged with {$tag->name}");

            $categories = ProductCategory::query()
                ->active()
                ->ordered()
                ->get();

            $products = Product::query()
                ->active()
                ->ordered()
                ->where(function ($query) use ($tag) {
                    $query->where('featured_tag_id', $tag->id)
                        ->orWhereHas('tags', fn ($q) => $q->where('tags.id', $tag->id));
                })
                ->with(['featuredMedia', 'tags', 'category', 'store', 'variants'])
                ->paginate(12);

            return view('products.index', [
                'categories' => $categories,
                'products' => $products,
                'currentCategory' => null,
                'currentTag' => $tag,
            ])->render();
        });

        return new Response($html);
    }

    /**
     * Display a single product.
     */
    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $cacheKey = "public:products:show:{$product->slug}";

        $html = PublicCacheService::remember($cacheKey, PublicCacheService::productTtl(), function () use ($product) {
            $this->seo->setBasic(
                $product->title,
                $product->short_description ?? $product->description
            );

            $product->load(['featuredMedia', 'tags', 'category', 'store', 'images', 'variants' => fn ($q) => $q->active()->ordered()]);

            $relatedProducts = Product::active()
                ->where('id', '!=', $product->id)
                ->when($product->product_category_id, fn ($q) => $q->where('product_category_id', $product->product_category_id))
                ->with(['featuredMedia', 'category', 'store', 'variants'])
                ->orderByRaw("CASE WHEN product_type = 'in_house' THEN 0 ELSE 1 END")
                ->inRandomOrder()
                ->limit(8)
                ->get();

            return view('products.show', [
                'product' => $product,
                'relatedProducts' => $relatedProducts,
            ])->render();
        });

        return new Response($html);
    }

    /**
     * Record a product view (called via IntersectionObserver from product cards).
     */
    public function recordView(Request $request, Product $product): JsonResponse
    {
        if (BotDetector::isBot($request->userAgent())) {
            return response()->json(['status' => 'skipped']);
        }

        RecordViewJob::dispatch([
            'type' => 'product',
            'model_id' => $product->id,
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
            'session_id' => session()->getId(),
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Track click and redirect to affiliate URL.
     */
    public function redirect(Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        ProductClick::record($product, auth()->id());

        AnalyticsService::flushProductCache();

        return redirect()->away($product->affiliate_url);
    }
}
