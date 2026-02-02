<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StoreAdPlacementRequest;
use App\Http\Requests\Cms\UpdateAdPlacementRequest;
use App\Models\AdPlacement;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdPlacementController extends Controller
{
    public function index(Request $request): Response
    {
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $allowedSorts = ['name', 'slot', 'priority', 'is_active', 'created_at'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }

        $query = AdPlacement::query()->with('category');

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where('name', 'ILIKE', "%{$search}%");
        }

        // Filter by slot
        if ($request->filled('slot')) {
            $query->where('slot', $request->get('slot'));
        }

        // Filter by category
        if ($request->filled('category_id')) {
            if ($request->get('category_id') === 'global') {
                $query->whereNull('category_id');
            } else {
                $query->where('category_id', $request->get('category_id'));
            }
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sort
        $direction = $sortDirection === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $direction);

        $adPlacements = $query->paginate(20)
            ->withQueryString()
            ->through(fn (AdPlacement $adPlacement) => [
                'id' => $adPlacement->id,
                'uuid' => $adPlacement->uuid,
                'name' => $adPlacement->name,
                'page_type' => $adPlacement->page_type,
                'page_type_label' => AdPlacement::getPageTypes()[$adPlacement->page_type] ?? $adPlacement->page_type,
                'slot' => $adPlacement->slot,
                'slot_label' => $adPlacement->slot_label,
                'category_id' => $adPlacement->category_id,
                'category_name' => $adPlacement->category?->name,
                'ad_code' => $adPlacement->ad_code,
                'is_active' => $adPlacement->is_active,
                'priority' => $adPlacement->priority,
                'created_at' => $adPlacement->created_at,
            ]);

        $categories = Category::query()
            ->orderByTranslatedName()
            ->get()
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ]);

        return Inertia::render('Settings/AdPlacements', [
            'adPlacements' => $adPlacements,
            'filters' => $request->only(['search', 'sort', 'direction', 'slot', 'category_id', 'is_active']),
            'categories' => $categories,
            'slots' => collect(AdPlacement::SLOTS)->map(fn ($label, $value) => [
                'value' => $value,
                'label' => $label,
            ])->values(),
            'pageTypes' => collect(AdPlacement::getPageTypes())->map(fn ($label, $value) => [
                'value' => $value,
                'label' => $label,
            ])->values(),
        ]);
    }

    public function store(StoreAdPlacementRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        AdPlacement::create([
            'name' => $validated['name'],
            'page_type' => $validated['page_type'],
            'slot' => $validated['slot'],
            'category_id' => $validated['category_id'] ?? null,
            'ad_code' => $validated['ad_code'],
            'is_active' => $validated['is_active'] ?? true,
            'priority' => $validated['priority'] ?? 0,
        ]);

        return redirect()->route('cms.ad-placements.index')
            ->with('success', 'Ad placement created successfully.');
    }

    public function edit(Request $request, AdPlacement $adPlacement): Response|\Illuminate\Http\JsonResponse
    {
        $adPlacement->load('category');

        $adPlacementData = [
            'id' => $adPlacement->id,
            'uuid' => $adPlacement->uuid,
            'name' => $adPlacement->name,
            'page_type' => $adPlacement->page_type,
            'slot' => $adPlacement->slot,
            'category_id' => $adPlacement->category_id,
            'category' => $adPlacement->category ? [
                'id' => $adPlacement->category->id,
                'name' => $adPlacement->category->name,
            ] : null,
            'ad_code' => $adPlacement->ad_code,
            'is_active' => $adPlacement->is_active,
            'priority' => $adPlacement->priority,
            'created_at' => $adPlacement->created_at,
        ];

        $categories = Category::query()
            ->orderByTranslatedName()
            ->get()
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ]);

        if ($request->wantsJson()) {
            return response()->json([
                'props' => [
                    'adPlacement' => $adPlacementData,
                    'categories' => $categories,
                    'slots' => collect(AdPlacement::SLOTS)->map(fn ($label, $value) => [
                        'value' => $value,
                        'label' => $label,
                    ])->values(),
                    'pageTypes' => collect(AdPlacement::getPageTypes())->map(fn ($label, $value) => [
                        'value' => $value,
                        'label' => $label,
                    ])->values(),
                ],
            ]);
        }

        return Inertia::render('Settings/AdPlacements', [
            'adPlacement' => $adPlacementData,
            'categories' => $categories,
            'slots' => collect(AdPlacement::SLOTS)->map(fn ($label, $value) => [
                'value' => $value,
                'label' => $label,
            ])->values(),
            'pageTypes' => collect(AdPlacement::getPageTypes())->map(fn ($label, $value) => [
                'value' => $value,
                'label' => $label,
            ])->values(),
        ]);
    }

    public function update(UpdateAdPlacementRequest $request, AdPlacement $adPlacement): RedirectResponse
    {
        $validated = $request->validated();

        $adPlacement->update([
            'name' => $validated['name'],
            'page_type' => $validated['page_type'],
            'slot' => $validated['slot'],
            'category_id' => $validated['category_id'] ?? null,
            'ad_code' => $validated['ad_code'],
            'is_active' => $validated['is_active'] ?? $adPlacement->is_active,
            'priority' => $validated['priority'] ?? $adPlacement->priority,
        ]);

        return redirect()->route('cms.ad-placements.index')
            ->with('success', 'Ad placement updated successfully.');
    }

    public function destroy(AdPlacement $adPlacement): RedirectResponse
    {
        $adPlacement->delete();

        return redirect()->route('cms.ad-placements.index')
            ->with('success', 'Ad placement deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:ad_placements,id'],
        ]);

        $count = AdPlacement::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('cms.ad-placements.index')
            ->with('success', "{$count} ad placements deleted successfully.");
    }
}
