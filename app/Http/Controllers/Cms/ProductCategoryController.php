<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StoreProductCategoryRequest;
use App\Http\Requests\Cms\UpdateProductCategoryRequest;
use App\Models\Language;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $sortField = $request->get('sort', 'order');
        $sortDirection = $request->get('direction', 'asc');

        $allowedSorts = ['name', 'slug', 'products_count', 'order', 'is_active', 'created_at'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'order';
        }

        $query = ProductCategory::query()->withCount('products')->with(['featuredMedia', 'parent']);

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->whereRaw('name::text ILIKE ?', ["%{$search}%"])
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sort
        $direction = $sortDirection === 'desc' ? 'desc' : 'asc';
        if ($sortField === 'name') {
            $query->orderByTranslatedName(app()->getLocale(), $direction);
        } else {
            $query->orderBy($sortField, $direction);
        }

        // Get active languages
        $activeLanguages = Language::active()->ordered()->get();

        $categories = $query->paginate(20)
            ->withQueryString()
            ->through(fn (ProductCategory $category) => [
                'id' => $category->id,
                'uuid' => $category->uuid,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'parent_name' => $category->parent?->name,
                'featured_image_url' => $category->featured_image_url,
                'is_active' => $category->is_active,
                'order' => $category->order,
                'products_count' => $category->products_count,
                'created_at' => $category->created_at,
                'translated_locales' => array_keys($category->getTranslations('name')),
            ]);

        // Get all categories for parent dropdown (excluding children to prevent circular references)
        $parentCategories = ProductCategory::whereNull('parent_id')
            ->active()
            ->ordered()
            ->get()
            ->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
            ]);

        return Inertia::render('ProductCategories/Index', [
            'categories' => $categories,
            'parentCategories' => $parentCategories,
            'filters' => $request->only(['search', 'sort', 'direction', 'is_active']),
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

        return Inertia::render('ProductCategories/Create', [
            'languages' => $languages->map(fn ($lang) => [
                'code' => $lang->code,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'direction' => $lang->direction,
            ]),
        ]);
    }

    public function store(StoreProductCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Handle translations - filter out empty values
        $name = $validated['name'];
        if (is_array($name)) {
            $name = array_filter($name, fn ($v) => $v !== null && $v !== '');
        }

        $description = $validated['description'] ?? null;
        if (is_array($description)) {
            $description = array_filter($description, fn ($v) => $v !== null && $v !== '');
            $description = empty($description) ? null : $description;
        }

        // Get max order
        $maxOrder = ProductCategory::max('order') ?? 0;

        ProductCategory::create([
            'name' => $name,
            'slug' => $validated['slug'] ?? null,
            'description' => $description,
            'parent_id' => $validated['parent_id'] ?? null,
            'featured_media_id' => $validated['featured_media_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'order' => $maxOrder + 1,
        ]);

        return redirect()->route('cms.product-categories.index')
            ->with('success', 'Product category created successfully.');
    }

    public function edit(Request $request, ProductCategory $productCategory): Response|JsonResponse
    {
        $productCategory->load('featuredMedia');
        $activeLanguages = Language::active()->ordered()->get();

        $categoryData = [
            'id' => $productCategory->id,
            'uuid' => $productCategory->uuid,
            'name' => $productCategory->name,
            'name_translations' => $productCategory->getTranslations('name'),
            'slug' => $productCategory->slug,
            'description' => $productCategory->description,
            'description_translations' => $productCategory->getTranslations('description'),
            'parent_id' => $productCategory->parent_id,
            'featured_media_id' => $productCategory->featured_media_id,
            'featured_media' => $productCategory->featuredMedia ? [
                'id' => $productCategory->featuredMedia->id,
                'url' => $productCategory->featuredMedia->url,
                'title' => $productCategory->featuredMedia->title,
            ] : null,
            'is_active' => $productCategory->is_active,
            'order' => $productCategory->order,
            'products_count' => $productCategory->products_count,
            'created_at' => $productCategory->created_at,
        ];

        $languageData = $activeLanguages->map(fn ($lang) => [
            'code' => $lang->code,
            'name' => $lang->name,
            'native_name' => $lang->native_name,
            'direction' => $lang->direction,
        ]);

        // Get parent categories (exclude self and its children)
        $parentCategories = ProductCategory::whereNull('parent_id')
            ->where('id', '!=', $productCategory->id)
            ->active()
            ->ordered()
            ->get()
            ->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
            ]);

        if ($request->wantsJson()) {
            return response()->json([
                'props' => [
                    'category' => $categoryData,
                    'languages' => $languageData,
                    'parentCategories' => $parentCategories,
                ],
            ]);
        }

        return Inertia::render('ProductCategories/Edit', [
            'category' => $categoryData,
            'languages' => $languageData,
            'parentCategories' => $parentCategories,
        ]);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): RedirectResponse
    {
        $validated = $request->validated();

        // Handle translations - filter out empty values
        $name = $validated['name'];
        if (is_array($name)) {
            $name = array_filter($name, fn ($v) => $v !== null && $v !== '');
        }

        $description = $validated['description'] ?? $productCategory->getTranslations('description');
        if (is_array($description)) {
            $description = array_filter($description, fn ($v) => $v !== null && $v !== '');
            $description = empty($description) ? null : $description;
        }

        $productCategory->update([
            'name' => $name,
            'slug' => $validated['slug'] ?? $productCategory->slug,
            'description' => $description,
            'parent_id' => array_key_exists('parent_id', $validated) ? $validated['parent_id'] : $productCategory->parent_id,
            'featured_media_id' => $validated['featured_media_id'] ?? $productCategory->featured_media_id,
            'is_active' => $validated['is_active'] ?? $productCategory->is_active,
        ]);

        return redirect()->route('cms.product-categories.index')
            ->with('success', 'Product category updated successfully.');
    }

    public function destroy(ProductCategory $productCategory): RedirectResponse
    {
        if ($productCategory->products()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete a category with associated products. Please remove products from this category first.');
        }

        $productCategory->delete();

        return redirect()->route('cms.product-categories.index')
            ->with('success', 'Product category deleted successfully.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:product_categories,id'],
            'items.*.order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['items'] as $item) {
            ProductCategory::where('id', $item['id'])->update([
                'order' => $item['order'],
            ]);
        }

        return redirect()->back()
            ->with('success', 'Categories reordered successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:product_categories,id'],
        ]);

        $ids = $validated['ids'];

        // Check for categories with products
        $categoriesWithProducts = ProductCategory::whereIn('id', $ids)
            ->whereHas('products')
            ->pluck('name')
            ->toArray();

        if (! empty($categoriesWithProducts)) {
            return redirect()->back()
                ->with('error', 'Cannot delete categories with products: '.implode(', ', $categoriesWithProducts));
        }

        $count = ProductCategory::whereIn('id', $ids)->delete();

        return redirect()->route('cms.product-categories.index')
            ->with('success', "{$count} categories deleted successfully.");
    }
}
