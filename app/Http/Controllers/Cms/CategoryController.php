<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StoreCategoryRequest;
use App\Http\Requests\Cms\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $isSearching = $request->filled('search');

        // Get all categories for tree view (only when not searching)
        $tree = [];
        if (! $isSearching) {
            $rootCategories = Category::query()
                ->whereNull('parent_id')
                ->withCount('posts')
                ->with(['children' => function ($q) {
                    $q->withCount('posts')->orderBy('order');
                }])
                ->orderBy('order')
                ->get();

            $tree = $rootCategories->map(fn ($cat) => $this->formatCategoryForTree($cat))->all();
        }

        // Build query for list view (with pagination)
        $query = Category::query()
            ->withCount('posts')
            ->with('parent:id,name');

        // Search
        if ($isSearching) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        $allowedSorts = ['name', 'slug', 'posts_count', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
        }

        // Paginate for list view
        $categories = $query->paginate(20)->withQueryString();

        // Get parent options for the create slideover
        $parentOptions = $this->getParentOptions();

        // Get total counts
        $totalCategories = Category::count();
        $rootCount = Category::whereNull('parent_id')->count();
        $childCount = Category::whereNotNull('parent_id')->count();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'tree' => $tree,
            'parentOptions' => $parentOptions,
            'filters' => $request->only(['search', 'sort', 'direction']),
            'counts' => [
                'total' => $totalCategories,
                'root' => $rootCount,
                'child' => $childCount,
            ],
        ]);
    }

    public function create(): Response
    {
        $parentOptions = $this->getParentOptions();

        return Inertia::render('Categories/Create', [
            'parentOptions' => $parentOptions,
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Get max order for the parent level
        $maxOrder = Category::where('parent_id', $validated['parent_id'] ?? null)
            ->max('order') ?? 0;

        Category::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? null,
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'order' => $maxOrder + 1,
        ]);

        return redirect()->route('cms.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category): Response
    {
        $category->load('parent');

        // Exclude current category and its descendants from parent options
        $excludeIds = $this->getDescendantIds($category);
        $excludeIds[] = $category->id;

        $parentOptions = $this->getParentOptions($excludeIds);

        return Inertia::render('Categories/Edit', [
            'category' => [
                'id' => $category->id,
                'uuid' => $category->uuid,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'order' => $category->order,
                'posts_count' => $category->posts_count,
                'parent' => $category->parent ? [
                    'id' => $category->parent->id,
                    'name' => $category->parent->name,
                ] : null,
            ],
            'parentOptions' => $parentOptions,
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $validated = $request->validated();

        $category->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? $category->slug,
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return redirect()->route('cms.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        // Check if category has children
        if ($category->hasChildren()) {
            return redirect()->back()
                ->with('error', 'Cannot delete a category with child categories. Please remove or reassign children first.');
        }

        // Check if category has posts
        if ($category->posts()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete a category with associated posts. Please remove posts from this category first.');
        }

        $category->delete();

        return redirect()->route('cms.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:categories,id'],
        ]);

        $ids = $validated['ids'];

        // Check for categories with children
        $categoriesWithChildren = Category::whereIn('id', $ids)
            ->whereHas('children')
            ->pluck('name')
            ->toArray();

        if (! empty($categoriesWithChildren)) {
            return redirect()->back()
                ->with('error', 'Cannot delete categories with children: '.implode(', ', $categoriesWithChildren));
        }

        // Check for categories with posts
        $categoriesWithPosts = Category::whereIn('id', $ids)
            ->whereHas('posts')
            ->pluck('name')
            ->toArray();

        if (! empty($categoriesWithPosts)) {
            return redirect()->back()
                ->with('error', 'Cannot delete categories with posts: '.implode(', ', $categoriesWithPosts));
        }

        $count = Category::whereIn('id', $ids)->delete();

        return redirect()->route('cms.categories.index')
            ->with('success', "{$count} categories deleted successfully.");
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:categories,id'],
            'items.*.order' => ['required', 'integer', 'min:0'],
            'items.*.parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        foreach ($validated['items'] as $item) {
            Category::where('id', $item['id'])->update([
                'order' => $item['order'],
                'parent_id' => $item['parent_id'] ?? null,
            ]);
        }

        return redirect()->back()
            ->with('success', 'Categories reordered successfully.');
    }

    private function formatCategoryForTree(Category $category): array
    {
        $result = [
            'id' => $category->id,
            'uuid' => $category->uuid,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'posts_count' => $category->posts_count,
            'order' => $category->order,
            'children' => [],
        ];

        if ($category->children && $category->children->count() > 0) {
            $result['children'] = $category->children
                ->map(fn ($child) => $this->formatCategoryForTree($child))
                ->all();
        }

        return $result;
    }

    /**
     * @param  array<int>  $excludeIds
     * @return array<int, array{id: int, name: string, depth: int}>
     */
    private function getParentOptions(array $excludeIds = []): array
    {
        $options = [];

        $rootCategories = Category::whereNull('parent_id')
            ->when(! empty($excludeIds), fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->with(['children' => fn ($q) => $q->when(! empty($excludeIds), fn ($q) => $q->whereNotIn('id', $excludeIds))->orderBy('order')])
            ->orderBy('order')
            ->get();

        foreach ($rootCategories as $category) {
            $this->addCategoryToOptions($options, $category, 0, $excludeIds);
        }

        return $options;
    }

    /**
     * @param  array<int, array{id: int, name: string, depth: int}>  $options
     * @param  array<int>  $excludeIds
     */
    private function addCategoryToOptions(array &$options, Category $category, int $depth, array $excludeIds = []): void
    {
        $prefix = str_repeat('— ', $depth);

        $options[] = [
            'id' => $category->id,
            'name' => $prefix.$category->name,
            'depth' => $depth,
        ];

        foreach ($category->children as $child) {
            if (! in_array($child->id, $excludeIds)) {
                $this->addCategoryToOptions($options, $child, $depth + 1, $excludeIds);
            }
        }
    }

    /**
     * @return array<int>
     */
    private function getDescendantIds(Category $category): array
    {
        $ids = [];

        $category->load('children');

        foreach ($category->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getDescendantIds($child));
        }

        return $ids;
    }
}
