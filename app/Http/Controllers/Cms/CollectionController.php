<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StoreCollectionRequest;
use App\Http\Requests\Cms\UpdateCollectionRequest;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Language;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function index(Request $request): Response
    {
        $sortField = $request->get('sort', 'order');
        $sortDirection = $request->get('direction', 'asc');

        $allowedSorts = ['name', 'slug', 'posts_count', 'order', 'created_at'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'order';
        }

        $query = Collection::query()->withCount('posts');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->whereTranslatedNameLike($search)
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $direction = $sortDirection === 'desc' ? 'desc' : 'asc';
        if ($sortField === 'name') {
            $query->orderByTranslatedName(app()->getLocale(), $direction);
        } else {
            $query->orderBy($sortField, $direction);
        }

        $activeLanguages = Language::active()->ordered()->get();

        $collections = $query->paginate(20)
            ->withQueryString()
            ->through(fn (Collection $collection) => [
                'id' => $collection->id,
                'uuid' => $collection->uuid,
                'name' => $collection->name,
                'slug' => $collection->slug,
                'description' => $collection->description,
                'is_active' => $collection->is_active,
                'order' => $collection->order,
                'posts_count' => $collection->posts_count,
                'created_at' => $collection->created_at,
                'translated_locales' => array_keys($collection->getTranslations('name')),
            ]);

        return Inertia::render('Collections/Index', [
            'collections' => $collections,
            'filters' => $request->only(['search', 'sort', 'direction']),
            'languages' => $activeLanguages->map(fn ($lang) => [
                'code' => $lang->code,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'direction' => $lang->direction,
            ]),
        ]);
    }

    /**
     * Shared data for create/edit forms.
     *
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $languages = Language::active()->ordered()->get();
        $categories = Category::orderByTranslatedName(app()->getLocale())->get();
        $tags = Tag::orderByTranslatedName(app()->getLocale())->get();

        return [
            'languages' => $languages->map(fn ($lang) => [
                'code' => $lang->code,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'direction' => $lang->direction,
            ]),
            'allCategories' => $categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
            ]),
            'allTags' => $tags->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
            ]),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('Collections/Create', $this->formData());
    }

    public function store(StoreCollectionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $name = $validated['name'];
        if (is_array($name)) {
            $name = array_filter($name, fn ($v) => $v !== null && $v !== '');
        }

        $description = $validated['description'] ?? null;
        if (is_array($description)) {
            $description = array_filter($description, fn ($v) => $v !== null && $v !== '') ?: null;
        }

        $collection = Collection::create([
            'name' => $name,
            'description' => $description,
            'slug' => $validated['slug'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'order' => $validated['order'] ?? 0,
            'sort_order' => $validated['sort_order'] ?? 'manual',
        ]);

        // Sync posts with order
        if (! empty($validated['posts'])) {
            $syncData = [];
            foreach ($validated['posts'] as $index => $postId) {
                $syncData[$postId] = ['order' => $index];
            }
            $collection->posts()->sync($syncData);
        }

        // Sync categories and tags
        $collection->categories()->sync($validated['categories'] ?? []);
        $collection->tags()->sync($validated['tags'] ?? []);

        return redirect()->route('cms.collections.edit', $collection)
            ->with('success', 'Collection created successfully.');
    }

    public function edit(Collection $collection): Response
    {
        $collectionData = [
            'id' => $collection->id,
            'uuid' => $collection->uuid,
            'name' => $collection->name,
            'name_translations' => $collection->getTranslations('name'),
            'description' => $collection->description,
            'description_translations' => $collection->getTranslations('description'),
            'slug' => $collection->slug,
            'is_active' => $collection->is_active,
            'order' => $collection->order,
            'sort_order' => $collection->sort_order,
            'posts_count' => $collection->posts_count,
            'posts' => $collection->posts()->with(['author', 'featuredMedia', 'categories'])->get()->map(fn ($post) => [
                'id' => $post->id,
                'uuid' => $post->uuid,
                'title' => $post->title,
                'slug' => $post->slug,
                'featured_image_thumb' => $post->featured_image_url,
                'author' => $post->author ? ['id' => $post->author->id, 'name' => $post->author->name] : null,
                'categories' => $post->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
                'status' => $post->status,
            ]),
            'categories' => $collection->categories->pluck('id'),
            'tags' => $collection->tags->pluck('id'),
            'created_at' => $collection->created_at,
        ];

        return Inertia::render('Collections/Edit', array_merge(
            ['collection' => $collectionData],
            $this->formData(),
        ));
    }

    public function update(UpdateCollectionRequest $request, Collection $collection): RedirectResponse
    {
        $validated = $request->validated();

        $name = $validated['name'];
        if (is_array($name)) {
            $name = array_filter($name, fn ($v) => $v !== null && $v !== '');
        }

        $description = $validated['description'] ?? null;
        if (is_array($description)) {
            $description = array_filter($description, fn ($v) => $v !== null && $v !== '') ?: null;
        }

        $collection->update([
            'name' => $name,
            'description' => $description,
            'slug' => $validated['slug'] ?? $collection->slug,
            'is_active' => $validated['is_active'] ?? $collection->is_active,
            'order' => $validated['order'] ?? $collection->order,
            'sort_order' => $validated['sort_order'] ?? $collection->sort_order,
        ]);

        // Sync posts with order
        if (isset($validated['posts'])) {
            $syncData = [];
            foreach ($validated['posts'] as $index => $postId) {
                $syncData[$postId] = ['order' => $index];
            }
            $collection->posts()->sync($syncData);
        }

        // Sync categories and tags
        if (isset($validated['categories'])) {
            $collection->categories()->sync($validated['categories']);
        }
        if (isset($validated['tags'])) {
            $collection->tags()->sync($validated['tags']);
        }

        return redirect()->route('cms.collections.edit', $collection)
            ->with('success', 'Collection updated successfully.');
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        $collection->delete();

        return redirect()->route('cms.collections.index')
            ->with('success', 'Collection deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:collections,id'],
        ]);

        $count = Collection::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('cms.collections.index')
            ->with('success', "{$count} collections deleted successfully.");
    }
}
