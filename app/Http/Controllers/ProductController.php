<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductClick;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

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
            ->with(['featuredMedia', 'tags', 'category'])
            ->get();

        return view('products.index', [
            'categories' => $categories,
            'products' => $products,
            'currentCategory' => null,
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
            ->with(['featuredMedia', 'tags', 'category'])
            ->get();

        return view('products.index', [
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
