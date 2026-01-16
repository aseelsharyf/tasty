<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StoreTagRequest;
use App\Http\Requests\Cms\UpdateTagRequest;
use App\Models\Language;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    /**
     * Search tags for autocomplete (JSON API)
     */
    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $search = $request->get('q', '');
        $exclude = $request->get('exclude', []); // IDs to exclude (already selected)
        $limit = min((int) $request->get('limit', 10), 50);

        $query = Tag::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereTranslatedNameLike($search)
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if (! empty($exclude)) {
            $query->whereNotIn('id', (array) $exclude);
        }

        $tags = $query
            ->orderByTranslatedName(app()->getLocale())
            ->limit($limit)
            ->get()
            ->map(fn (Tag $tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ]);

        return response()->json($tags);
    }

    public function index(Request $request): Response
    {
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');

        $allowedSorts = ['name', 'slug', 'posts_count', 'created_at'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'name';
        }

        $query = Tag::query()->withCount('posts');

        // Search - use model scope for translatable name column
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->whereTranslatedNameLike($search)
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Sort - use scope for name, otherwise normal column
        $direction = $sortDirection === 'desc' ? 'desc' : 'asc';
        if ($sortField === 'name') {
            $query->orderByTranslatedName(app()->getLocale(), $direction);
        } else {
            $query->orderBy($sortField, $direction);
        }

        // Get active languages for translation status
        $activeLanguages = Language::active()->ordered()->get();

        $tags = $query->paginate(20)
            ->withQueryString()
            ->through(fn (Tag $tag) => [
                'id' => $tag->id,
                'uuid' => $tag->uuid,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'posts_count' => $tag->posts_count,
                'created_at' => $tag->created_at,
                'translated_locales' => array_keys($tag->getTranslations('name')),
            ]);

        return Inertia::render('Tags/Index', [
            'tags' => $tags,
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

        return Inertia::render('Tags/Create', [
            'languages' => $languages->map(fn ($lang) => [
                'code' => $lang->code,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'direction' => $lang->direction,
            ]),
        ]);
    }

    public function store(StoreTagRequest $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        // Handle translations - filter out empty values
        $name = $validated['name'];
        if (is_array($name)) {
            $name = array_filter($name, fn ($v) => $v !== null && $v !== '');
        }

        $tag = Tag::create([
            'name' => $name,
            'slug' => $validated['slug'] ?? null,
        ]);

        // Return JSON for AJAX requests (inline tag creation from post editor)
        if ($request->wantsJson()) {
            return response()->json([
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ]);
        }

        return redirect()->route('cms.tags.index')
            ->with('success', 'Tag created successfully.');
    }

    public function edit(Request $request, Tag $tag): Response|\Illuminate\Http\JsonResponse
    {
        $activeLanguages = Language::active()->ordered()->get();

        $tagData = [
            'id' => $tag->id,
            'uuid' => $tag->uuid,
            'name' => $tag->name, // Current locale translation
            'name_translations' => $tag->getTranslations('name'),
            'slug' => $tag->slug,
            'posts_count' => $tag->posts_count,
            'created_at' => $tag->created_at,
        ];

        $languageData = $activeLanguages->map(fn ($lang) => [
            'code' => $lang->code,
            'name' => $lang->name,
            'native_name' => $lang->native_name,
            'direction' => $lang->direction,
        ]);

        // Return JSON for AJAX requests
        if ($request->wantsJson()) {
            return response()->json([
                'props' => [
                    'tag' => $tagData,
                    'languages' => $languageData,
                ],
            ]);
        }

        return Inertia::render('Tags/Edit', [
            'tag' => $tagData,
            'languages' => $languageData,
        ]);
    }

    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $validated = $request->validated();

        // Handle translations - filter out empty values
        $name = $validated['name'];
        if (is_array($name)) {
            $name = array_filter($name, fn ($v) => $v !== null && $v !== '');
        }

        $tag->update([
            'name' => $name,
            'slug' => $validated['slug'] ?? $tag->slug,
        ]);

        return redirect()->route('cms.tags.index')
            ->with('success', 'Tag updated successfully.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        // Check if tag has posts
        if ($tag->posts()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete a tag with associated posts. Please remove posts from this tag first.');
        }

        $tag->delete();

        return redirect()->route('cms.tags.index')
            ->with('success', 'Tag deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:tags,id'],
        ]);

        $ids = $validated['ids'];

        // Check for tags with posts
        $tagsWithPosts = Tag::whereIn('id', $ids)
            ->whereHas('posts')
            ->pluck('name')
            ->toArray();

        if (! empty($tagsWithPosts)) {
            return redirect()->back()
                ->with('error', 'Cannot delete tags with posts: '.implode(', ', $tagsWithPosts));
        }

        $count = Tag::whereIn('id', $ids)->delete();

        return redirect()->route('cms.tags.index')
            ->with('success', "{$count} tags deleted successfully.");
    }
}
