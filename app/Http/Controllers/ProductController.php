<?php

namespace App\Http\Controllers;

use App\Jobs\RecordViewJob;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductClick;
use App\Models\ProductStore;
use App\Services\SeoService;
use App\Support\BotDetector;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected SeoService $seo
    ) {}

    /**
     * Display all products with category filters.
     */
    public function index(): View
    {
        $this->seo->setProductsIndex();

        $categories = ProductCategory::query()
            ->active()
            ->ordered()
            ->get();

        $products = Product::query()
            ->active()
            ->ordered()
            ->with(['featuredMedia', 'tags', 'category', 'store'])
            ->paginate(12);

        return view('products.index', [
            'categories' => $categories,
            'products' => $products,
            'currentCategory' => null,
        ]);
    }

    /**
     * Display all products from a specific store with pagination.
     */
    public function byStore(ProductStore $store): View
    {
        abort_unless($store->is_active, 404);

        $this->seo->setProductStore($store);

        $products = $store->products()
            ->active()
            ->ordered()
            ->with(['featuredMedia', 'tags', 'category', 'store'])
            ->paginate(12);

        return view('products.store', [
            'store' => $store,
            'products' => $products,
        ]);
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
            ->with(['featuredMedia', 'tags', 'category', 'store'])
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
    public function byCategory(ProductCategory $category): View
    {
        abort_unless($category->is_active, 404);

        $this->seo->setProductCategory($category);

        $categories = ProductCategory::query()
            ->active()
            ->ordered()
            ->get();

        $products = $category->products()
            ->active()
            ->ordered()
            ->with(['featuredMedia', 'tags', 'category', 'store'])
            ->paginate(12);

        return view('products.category', [
            'categories' => $categories,
            'products' => $products,
            'currentCategory' => $category,
        ]);
    }

    /**
     * Display products for a specific tag.
     */
    public function byTag(\App\Models\Tag $tag): View
    {
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
            ->with(['featuredMedia', 'tags', 'category', 'store'])
            ->paginate(12);

        return view('products.index', [
            'categories' => $categories,
            'products' => $products,
            'currentCategory' => null,
            'currentTag' => $tag,
        ]);
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

        return redirect()->away($product->affiliate_url);
    }
}
