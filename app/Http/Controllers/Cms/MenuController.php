<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StoreMenuRequest;
use App\Http\Requests\Cms\UpdateMenuRequest;
use App\Models\Category;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Menu::query()
            ->withCount('allItems');

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->whereTranslatedNameLike($search)
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['name', 'location', 'created_at', 'is_active'];
        $direction = $sortDirection === 'desc' ? 'desc' : 'asc';

        if ($sortField === 'name') {
            $query->orderByTranslatedName(app()->getLocale(), $direction);
        } elseif (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $activeLanguages = Language::active()->ordered()->get();

        $menus = $query->paginate(20)
            ->withQueryString()
            ->through(fn (Menu $menu) => [
                'id' => $menu->id,
                'uuid' => $menu->uuid,
                'name' => $menu->name,
                'location' => $menu->location,
                'description' => $menu->description,
                'is_active' => $menu->is_active,
                'all_items_count' => $menu->all_items_count,
                'created_at' => $menu->created_at,
                'translated_locales' => array_keys($menu->getTranslations('name')),
            ]);

        return Inertia::render('Menus/Index', [
            'menus' => $menus,
            'filters' => $request->only(['search', 'sort', 'direction']),
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

        return Inertia::render('Menus/Create', [
            'languages' => $languages->map(fn ($lang) => [
                'code' => $lang->code,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'direction' => $lang->direction,
            ]),
        ]);
    }

    public function store(StoreMenuRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Handle translations
        $name = $validated['name'];
        if (is_array($name)) {
            $name = array_filter($name, fn ($v) => $v !== null && $v !== '');
        }

        $description = $validated['description'] ?? null;
        if (is_array($description)) {
            $description = array_filter($description, fn ($v) => $v !== null && $v !== '');
            $description = empty($description) ? null : $description;
        }

        $menu = Menu::create([
            'name' => $name,
            'location' => $validated['location'],
            'description' => $description,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('cms.menus.edit', $menu->uuid)
            ->with('success', 'Menu created successfully. Now add some items!');
    }

    public function edit(Request $request, Menu $menu): Response|\Illuminate\Http\JsonResponse
    {
        $languages = Language::active()->ordered()->get();

        // Load menu items tree
        $items = $this->formatMenuItemsTree($menu->getTree());

        // Get categories and posts for linking
        $categories = Category::query()
            ->orderByTranslatedName(app()->getLocale())
            ->get()
            ->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
            ]);

        $languagesData = $languages->map(fn ($lang) => [
            'code' => $lang->code,
            'name' => $lang->name,
            'native_name' => $lang->native_name,
            'direction' => $lang->direction,
        ]);

        $menuData = [
            'id' => $menu->id,
            'uuid' => $menu->uuid,
            'name' => $menu->name,
            'name_translations' => $menu->getTranslations('name'),
            'location' => $menu->location,
            'description' => $menu->description,
            'description_translations' => $menu->getTranslations('description'),
            'is_active' => $menu->is_active,
            'items' => $items,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'props' => [
                    'menu' => $menuData,
                    'languages' => $languagesData,
                    'categories' => $categories,
                    'itemTypes' => MenuItem::getTypes(),
                ],
            ]);
        }

        return Inertia::render('Menus/Edit', [
            'menu' => $menuData,
            'languages' => $languagesData,
            'categories' => $categories,
            'itemTypes' => MenuItem::getTypes(),
        ]);
    }

    public function update(UpdateMenuRequest $request, Menu $menu): RedirectResponse
    {
        $validated = $request->validated();

        // Handle translations
        $name = $validated['name'];
        if (is_array($name)) {
            $name = array_filter($name, fn ($v) => $v !== null && $v !== '');
        }

        $description = $validated['description'] ?? null;
        if (is_array($description)) {
            $description = array_filter($description, fn ($v) => $v !== null && $v !== '');
            $description = empty($description) ? null : $description;
        }

        $menu->update([
            'name' => $name,
            'location' => $validated['location'],
            'description' => $description,
            'is_active' => $validated['is_active'] ?? $menu->is_active,
        ]);

        return redirect()->back()
            ->with('success', 'Menu updated successfully.');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();

        return redirect()->route('cms.menus.index')
            ->with('success', 'Menu deleted successfully.');
    }

    /**
     * Add an item to the menu.
     */
    public function addItem(Request $request, Menu $menu): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required'],
            'label.*' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:custom,external,category,post'],
            'url' => ['nullable', 'string', 'max:500'],
            'linkable_id' => ['nullable', 'integer'],
            'parent_id' => ['nullable', 'integer', 'exists:menu_items,id'],
            'target' => ['nullable', 'string', 'in:_self,_blank'],
            'icon' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Handle translations
        $label = $validated['label'];
        if (is_array($label)) {
            $label = array_filter($label, fn ($v) => $v !== null && $v !== '');
        }

        // Get max order for the parent level
        $maxOrder = MenuItem::where('menu_id', $menu->id)
            ->where('parent_id', $validated['parent_id'] ?? null)
            ->max('order') ?? 0;

        // Determine linkable type/id based on type
        $linkableType = null;
        $linkableId = null;
        $url = $validated['url'] ?? null;

        if ($validated['type'] === MenuItem::TYPE_CATEGORY && ! empty($validated['linkable_id'])) {
            $linkableType = Category::class;
            $linkableId = $validated['linkable_id'];
        } elseif ($validated['type'] === MenuItem::TYPE_POST && ! empty($validated['linkable_id'])) {
            $linkableType = Post::class;
            $linkableId = $validated['linkable_id'];
        }

        MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'label' => $label,
            'type' => $validated['type'],
            'url' => $url,
            'linkable_type' => $linkableType,
            'linkable_id' => $linkableId,
            'target' => $validated['target'] ?? '_self',
            'icon' => $validated['icon'] ?? null,
            'order' => $maxOrder + 1,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->back()
            ->with('success', 'Menu item added successfully.');
    }

    /**
     * Update a menu item.
     */
    public function updateItem(Request $request, Menu $menu, MenuItem $item): RedirectResponse
    {
        // Verify item belongs to menu
        if ($item->menu_id !== $menu->id) {
            abort(404);
        }

        $validated = $request->validate([
            'label' => ['required'],
            'label.*' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:custom,external,category,post'],
            'url' => ['nullable', 'string', 'max:500'],
            'linkable_id' => ['nullable', 'integer'],
            'target' => ['nullable', 'string', 'in:_self,_blank'],
            'icon' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Handle translations
        $label = $validated['label'];
        if (is_array($label)) {
            $label = array_filter($label, fn ($v) => $v !== null && $v !== '');
        }

        // Determine linkable type/id based on type
        $linkableType = null;
        $linkableId = null;
        $url = $validated['url'] ?? null;

        if ($validated['type'] === MenuItem::TYPE_CATEGORY && ! empty($validated['linkable_id'])) {
            $linkableType = Category::class;
            $linkableId = $validated['linkable_id'];
        } elseif ($validated['type'] === MenuItem::TYPE_POST && ! empty($validated['linkable_id'])) {
            $linkableType = Post::class;
            $linkableId = $validated['linkable_id'];
        }

        $item->update([
            'label' => $label,
            'type' => $validated['type'],
            'url' => $url,
            'linkable_type' => $linkableType,
            'linkable_id' => $linkableId,
            'target' => $validated['target'] ?? '_self',
            'icon' => $validated['icon'] ?? null,
            'is_active' => $validated['is_active'] ?? $item->is_active,
        ]);

        return redirect()->back()
            ->with('success', 'Menu item updated successfully.');
    }

    /**
     * Delete a menu item.
     */
    public function destroyItem(Menu $menu, MenuItem $item): RedirectResponse
    {
        // Verify item belongs to menu
        if ($item->menu_id !== $menu->id) {
            abort(404);
        }

        // Move children to parent level
        if ($item->hasChildren()) {
            MenuItem::where('parent_id', $item->id)
                ->update(['parent_id' => $item->parent_id]);
        }

        $item->delete();

        return redirect()->back()
            ->with('success', 'Menu item deleted successfully.');
    }

    /**
     * Reorder menu items (drag and drop).
     */
    public function reorderItems(Request $request, Menu $menu): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.order' => ['required', 'integer', 'min:0'],
            'items.*.parent_id' => ['nullable', 'integer', 'exists:menu_items,id'],
        ]);

        foreach ($validated['items'] as $itemData) {
            MenuItem::where('id', $itemData['id'])
                ->where('menu_id', $menu->id)
                ->update([
                    'order' => $itemData['order'],
                    'parent_id' => $itemData['parent_id'] ?? null,
                ]);
        }

        return redirect()->back()
            ->with('success', 'Menu items reordered successfully.');
    }

    /**
     * Format menu items for tree display.
     *
     * @param  \Illuminate\Support\Collection<int, MenuItem>  $items
     * @return array<int, array<string, mixed>>
     */
    private function formatMenuItemsTree($items): array
    {
        return $items->map(function (MenuItem $item) {
            $result = [
                'id' => $item->id,
                'uuid' => $item->uuid,
                'label' => $item->label,
                'label_translations' => $item->getTranslations('label'),
                'type' => $item->type,
                'url' => $item->url,
                'linkable_type' => $item->linkable_type,
                'linkable_id' => $item->linkable_id,
                'target' => $item->target,
                'icon' => $item->icon,
                'order' => $item->order,
                'is_active' => $item->is_active,
                'parent_id' => $item->parent_id,
                'children' => [],
            ];

            if ($item->children && $item->children->count() > 0) {
                $result['children'] = $this->formatMenuItemsTree($item->children);
            }

            return $result;
        })->all();
    }
}
