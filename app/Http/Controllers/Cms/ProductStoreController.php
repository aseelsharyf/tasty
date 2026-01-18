<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StoreProductStoreRequest;
use App\Http\Requests\Cms\UpdateProductStoreRequest;
use App\Models\ProductStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductStoreController extends Controller
{
    public function index(Request $request): Response
    {
        $sortField = $request->get('sort', 'order');
        $sortDirection = $request->get('direction', 'asc');

        $allowedSorts = ['name', 'products_count', 'order', 'is_active', 'created_at'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'order';
        }

        $query = ProductStore::query()->withCount('products')->with('logo');

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('hotline', 'like', "%{$search}%")
                    ->orWhere('address', 'ILIKE', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sort
        $direction = $sortDirection === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $direction);

        $stores = $query->paginate(20)
            ->withQueryString()
            ->through(fn (ProductStore $store) => [
                'id' => $store->id,
                'uuid' => $store->uuid,
                'name' => $store->name,
                'business_type' => $store->business_type,
                'address' => $store->address,
                'location_label' => $store->location_label,
                'hotline' => $store->hotline,
                'contact_email' => $store->contact_email,
                'website_url' => $store->website_url,
                'logo_url' => $store->logo_url,
                'is_active' => $store->is_active,
                'order' => $store->order,
                'products_count' => $store->products_count,
                'created_at' => $store->created_at,
            ]);

        return Inertia::render('ProductStores/Index', [
            'stores' => $stores,
            'filters' => $request->only(['search', 'sort', 'direction', 'is_active']),
        ]);
    }

    public function create(): RedirectResponse
    {
        // Slideover is used for creation, redirect to index
        return redirect()->route('cms.product-stores.index');
    }

    public function store(StoreProductStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Get max order
        $maxOrder = ProductStore::max('order') ?? 0;

        ProductStore::create([
            'name' => $validated['name'],
            'business_type' => $validated['business_type'] ?? null,
            'address' => $validated['address'] ?? null,
            'location_label' => $validated['location_label'] ?? null,
            'logo_media_id' => $validated['logo_media_id'] ?? null,
            'hotline' => $validated['hotline'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'website_url' => $validated['website_url'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'order' => $maxOrder + 1,
        ]);

        return redirect()->route('cms.product-stores.index')
            ->with('success', 'Product store created successfully.');
    }

    public function edit(Request $request, ProductStore $productStore): JsonResponse|RedirectResponse
    {
        $productStore->load('logo');

        $storeData = [
            'id' => $productStore->id,
            'uuid' => $productStore->uuid,
            'name' => $productStore->name,
            'business_type' => $productStore->business_type,
            'address' => $productStore->address,
            'location_label' => $productStore->location_label,
            'hotline' => $productStore->hotline,
            'contact_email' => $productStore->contact_email,
            'website_url' => $productStore->website_url,
            'logo_media_id' => $productStore->logo_media_id,
            'logo' => $productStore->logo ? [
                'id' => $productStore->logo->id,
                'url' => $productStore->logo->url,
                'thumbnail_url' => $productStore->logo->thumbnail_url,
                'title' => $productStore->logo->title,
            ] : null,
            'is_active' => $productStore->is_active,
            'order' => $productStore->order,
            'products_count' => $productStore->products_count,
            'created_at' => $productStore->created_at,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'props' => [
                    'store' => $storeData,
                ],
            ]);
        }

        // Slideover is used for editing, redirect to index
        return redirect()->route('cms.product-stores.index');
    }

    public function update(UpdateProductStoreRequest $request, ProductStore $productStore): RedirectResponse
    {
        $validated = $request->validated();

        $productStore->update([
            'name' => $validated['name'],
            'business_type' => $validated['business_type'] ?? $productStore->business_type,
            'address' => $validated['address'] ?? $productStore->address,
            'location_label' => $validated['location_label'] ?? $productStore->location_label,
            'logo_media_id' => $validated['logo_media_id'] ?? $productStore->logo_media_id,
            'hotline' => $validated['hotline'] ?? $productStore->hotline,
            'contact_email' => $validated['contact_email'] ?? $productStore->contact_email,
            'website_url' => $validated['website_url'] ?? $productStore->website_url,
            'is_active' => $validated['is_active'] ?? $productStore->is_active,
        ]);

        return redirect()->route('cms.product-stores.index')
            ->with('success', 'Product store updated successfully.');
    }

    public function destroy(ProductStore $productStore): RedirectResponse
    {
        if ($productStore->products()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete a store with associated products. Please remove products from this store first.');
        }

        $productStore->delete();

        return redirect()->route('cms.product-stores.index')
            ->with('success', 'Product store deleted successfully.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:product_stores,id'],
            'items.*.order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['items'] as $item) {
            ProductStore::where('id', $item['id'])->update([
                'order' => $item['order'],
            ]);
        }

        return redirect()->back()
            ->with('success', 'Stores reordered successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:product_stores,id'],
        ]);

        $ids = $validated['ids'];

        // Check for stores with products
        $storesWithProducts = ProductStore::whereIn('id', $ids)
            ->whereHas('products')
            ->pluck('name')
            ->toArray();

        if (! empty($storesWithProducts)) {
            return redirect()->back()
                ->with('error', 'Cannot delete stores with products: '.implode(', ', $storesWithProducts));
        }

        $count = ProductStore::whereIn('id', $ids)->delete();

        return redirect()->route('cms.product-stores.index')
            ->with('success', "{$count} stores deleted successfully.");
    }
}
