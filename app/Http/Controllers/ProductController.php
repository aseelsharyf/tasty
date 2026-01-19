<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductClick;
use App\Models\ProductStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display all products with category filters.
     */
    public function index(): View
    {
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
     * Track click and redirect to affiliate URL.
     */
    public function redirect(Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        ProductClick::record($product, auth()->id());

        return redirect()->away($product->affiliate_url);
    }
}
