<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StoreProductRequest;
use App\Http\Requests\Cms\UpdateProductRequest;
use App\Models\Language;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStore;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CmsProductController extends Controller
{
    public function index(Request $request): Response
    {
        $sortField = $request->get('sort', 'order');
        $sortDirection = $request->get('direction', 'asc');

        $allowedSorts = ['title', 'slug', 'price', 'order', 'is_active', 'created_at', 'click_count'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'order';
        }

        $query = Product::query()
            ->withCount('clicks')
            ->with(['featuredMedia', 'category', 'store', 'featuredTag', 'tags']);

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->whereRaw('title::text ILIKE ?', ["%{$search}%"])
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('product_category_id', $request->get('category_id'));
        }

        // Sort
        $direction = $sortDirection === 'desc' ? 'desc' : 'asc';
        if ($sortField === 'title') {
            $query->orderByTranslatedTitle(app()->getLocale(), $direction);
        } elseif ($sortField === 'click_count') {
            $query->orderBy('clicks_count', $direction);
        } else {
            $query->orderBy($sortField, $direction);
        }

        // Get active languages
        $activeLanguages = Language::active()->ordered()->get();

        // Get categories for filter
        $categories = ProductCategory::active()->ordered()->get();

        // Get tags for product form
        $tags = Tag::orderByTranslatedName(app()->getLocale())->get();

        // Get stores for filter
        $stores = ProductStore::active()->ordered()->get();

        $products = $query->paginate(20)
            ->withQueryString()
            ->through(fn (Product $product) => [
                'id' => $product->id,
                'uuid' => $product->uuid,
                'title' => $product->title,
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => $product->price,
                'formatted_price' => $product->formatted_price,
                'currency' => $product->currency,
                'affiliate_url' => $product->affiliate_url,
                'product_store_id' => $product->product_store_id,
                'store' => $product->store ? [
                    'id' => $product->store->id,
                    'name' => $product->store->name,
                ] : null,
                'featured_image_url' => $product->featured_image_url,
                'is_active' => $product->is_active,
                'order' => $product->order,
                'clicks_count' => $product->clicks_count,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                ] : null,
                'tags' => $product->tags->map(fn ($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                ])->toArray(),
                'created_at' => $product->created_at,
                'translated_locales' => array_keys($product->getTranslations('title')),
            ]);

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
            ]),
            'tags' => $tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
            ]),
            'stores' => $stores->map(fn ($store) => [
                'id' => $store->id,
                'name' => $store->name,
            ]),
            'filters' => $request->only(['search', 'sort', 'direction', 'is_active', 'category_id']),
            'languages' => $activeLanguages->map(fn ($lang) => [
                'code' => $lang->code,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'direction' => $lang->direction,
            ]),
        ]);
    }

    public function create(): Response
    {
        $languages = Language::active()->ordered()->get();
        $categories = ProductCategory::active()->ordered()->get();
        $tags = Tag::orderByTranslatedName(app()->getLocale())->get();
        $stores = ProductStore::active()->ordered()->get();

        return Inertia::render('Products/Create', [
            'categories' => $categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
            ]),
            'tags' => $tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
            ]),
            'stores' => $stores->map(fn ($store) => [
                'id' => $store->id,
                'name' => $store->name,
            ]),
            'languages' => $languages->map(fn ($lang) => [
                'code' => $lang->code,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'direction' => $lang->direction,
            ]),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Handle translations - filter out empty values
        $title = $validated['title'];
        if (is_array($title)) {
            $title = array_filter($title, fn ($v) => $v !== null && $v !== '');
        }

        $description = $validated['description'] ?? null;
        if (is_array($description)) {
            $description = array_filter($description, fn ($v) => $v !== null && $v !== '');
            $description = empty($description) ? null : $description;
        }

        $shortDescription = $validated['short_description'] ?? null;
        if (is_array($shortDescription)) {
            $shortDescription = array_filter($shortDescription, fn ($v) => $v !== null && $v !== '');
            $shortDescription = empty($shortDescription) ? null : $shortDescription;
        }

        // Get max order for the category
        $maxOrder = Product::where('product_category_id', $validated['product_category_id'])
            ->max('order') ?? 0;

        $product = Product::create([
            'title' => $title,
            'slug' => $validated['slug'] ?? null,
            'description' => $description,
            'short_description' => $shortDescription,
            'brand' => $validated['brand'] ?? null,
            'product_category_id' => $validated['product_category_id'],
            'product_store_id' => $validated['product_store_id'] ?? null,
            'featured_tag_id' => $validated['featured_tag_id'] ?? null,
            'featured_media_id' => $validated['featured_media_id'] ?? null,
            'price' => $validated['price'] ?? null,
            'currency' => $validated['currency'] ?? 'USD',
            'availability' => $validated['availability'] ?? 'in_stock',
            'affiliate_url' => $validated['affiliate_url'],
            'is_active' => $validated['is_active'] ?? true,
            'is_featured' => $validated['is_featured'] ?? false,
            'order' => $maxOrder + 1,
            'sku' => $validated['sku'] ?? null,
            'compare_at_price' => $validated['compare_at_price'] ?? null,
        ]);

        // Sync tags
        if (isset($validated['tag_ids'])) {
            $product->tags()->sync($validated['tag_ids']);
        }

        // Sync images
        if (isset($validated['image_ids'])) {
            $imageData = [];
            foreach ($validated['image_ids'] as $order => $imageId) {
                $imageData[$imageId] = ['order' => $order];
            }
            $product->images()->sync($imageData);
        }

        return redirect()->route('cms.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Request $request, Product $product): Response|JsonResponse
    {
        $product->load(['featuredMedia', 'category', 'store', 'featuredTag', 'tags', 'images']);
        $activeLanguages = Language::active()->ordered()->get();
        $categories = ProductCategory::active()->ordered()->get();
        $tags = Tag::orderByTranslatedName(app()->getLocale())->get();
        $stores = ProductStore::active()->ordered()->get();

        $productData = [
            'id' => $product->id,
            'uuid' => $product->uuid,
            'title' => $product->title,
            'title_translations' => $product->getTranslations('title'),
            'slug' => $product->slug,
            'description' => $product->description,
            'description_translations' => $product->getTranslations('description'),
            'short_description' => $product->short_description,
            'short_description_translations' => $product->getTranslations('short_description'),
            'brand' => $product->brand,
            'product_category_id' => $product->product_category_id,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
            ] : null,
            'featured_tag_id' => $product->featured_tag_id,
            'featured_tag' => $product->featuredTag ? [
                'id' => $product->featuredTag->id,
                'name' => $product->featuredTag->name,
            ] : null,
            'featured_media_id' => $product->featured_media_id,
            'featured_media' => $product->featuredMedia ? [
                'id' => $product->featuredMedia->id,
                'url' => $product->featuredMedia->url,
                'title' => $product->featuredMedia->title,
            ] : null,
            'price' => $product->price,
            'currency' => $product->currency,
            'compare_at_price' => $product->compare_at_price,
            'availability' => $product->availability,
            'affiliate_url' => $product->affiliate_url,
            'product_store_id' => $product->product_store_id,
            'store' => $product->store ? [
                'id' => $product->store->id,
                'name' => $product->store->name,
            ] : null,
            'is_active' => $product->is_active,
            'is_featured' => $product->is_featured,
            'order' => $product->order,
            'sku' => $product->sku,
            'tag_ids' => $product->tags->pluck('id')->toArray(),
            'tags' => $product->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
            ])->toArray(),
            'image_ids' => $product->images->pluck('id')->toArray(),
            'images' => $product->images->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->url,
                'thumbnail_url' => $image->thumbnail_url,
                'title' => $image->title,
            ])->toArray(),
            'clicks_count' => $product->clicks()->count(),
            'created_at' => $product->created_at,
        ];

        $languageData = $activeLanguages->map(fn ($lang) => [
            'code' => $lang->code,
            'name' => $lang->name,
            'native_name' => $lang->native_name,
            'direction' => $lang->direction,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'props' => [
                    'product' => $productData,
                    'categories' => $categories->map(fn ($cat) => [
                        'id' => $cat->id,
                        'name' => $cat->name,
                    ]),
                    'tags' => $tags->map(fn ($tag) => [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ]),
                    'stores' => $stores->map(fn ($store) => [
                        'id' => $store->id,
                        'name' => $store->name,
                    ]),
                    'languages' => $languageData,
                ],
            ]);
        }

        return Inertia::render('Products/Edit', [
            'product' => $productData,
            'categories' => $categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
            ]),
            'tags' => $tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
            ]),
            'stores' => $stores->map(fn ($store) => [
                'id' => $store->id,
                'name' => $store->name,
            ]),
            'languages' => $languageData,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();

        // Handle translations - filter out empty values
        $title = $validated['title'];
        if (is_array($title)) {
            $title = array_filter($title, fn ($v) => $v !== null && $v !== '');
        }

        $description = $validated['description'] ?? $product->getTranslations('description');
        if (is_array($description)) {
            $description = array_filter($description, fn ($v) => $v !== null && $v !== '');
            $description = empty($description) ? null : $description;
        }

        $shortDescription = $validated['short_description'] ?? $product->getTranslations('short_description');
        if (is_array($shortDescription)) {
            $shortDescription = array_filter($shortDescription, fn ($v) => $v !== null && $v !== '');
            $shortDescription = empty($shortDescription) ? null : $shortDescription;
        }

        $product->update([
            'title' => $title,
            'slug' => $validated['slug'] ?? $product->slug,
            'description' => $description,
            'short_description' => $shortDescription,
            'brand' => array_key_exists('brand', $validated) ? $validated['brand'] : $product->brand,
            'product_category_id' => $validated['product_category_id'] ?? $product->product_category_id,
            'featured_tag_id' => array_key_exists('featured_tag_id', $validated) ? $validated['featured_tag_id'] : $product->featured_tag_id,
            'featured_media_id' => $validated['featured_media_id'] ?? $product->featured_media_id,
            'price' => $validated['price'] ?? $product->price,
            'currency' => $validated['currency'] ?? $product->currency,
            'compare_at_price' => $validated['compare_at_price'] ?? $product->compare_at_price,
            'availability' => $validated['availability'] ?? $product->availability,
            'affiliate_url' => $validated['affiliate_url'] ?? $product->affiliate_url,
            'product_store_id' => array_key_exists('product_store_id', $validated) ? $validated['product_store_id'] : $product->product_store_id,
            'is_active' => $validated['is_active'] ?? $product->is_active,
            'is_featured' => $validated['is_featured'] ?? $product->is_featured,
            'sku' => $validated['sku'] ?? $product->sku,
        ]);

        // Sync tags
        if (isset($validated['tag_ids'])) {
            $product->tags()->sync($validated['tag_ids']);
        }

        // Sync images
        if (isset($validated['image_ids'])) {
            $imageData = [];
            foreach ($validated['image_ids'] as $order => $imageId) {
                $imageData[$imageId] = ['order' => $order];
            }
            $product->images()->sync($imageData);
        }

        return redirect()->route('cms.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('cms.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:products,id'],
            'items.*.order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['items'] as $item) {
            Product::where('id', $item['id'])->update([
                'order' => $item['order'],
            ]);
        }

        return redirect()->back()
            ->with('success', 'Products reordered successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:products,id'],
        ]);

        $count = Product::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('cms.products.index')
            ->with('success', "{$count} products deleted successfully.");
    }
}
