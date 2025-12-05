<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StoreTagRequest;
use App\Http\Requests\Cms\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function index(Request $request): Response
    {
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');

        $allowedSorts = ['name', 'slug', 'posts_count', 'created_at'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'name';
        }

        $query = Tag::query()->withCount('posts');

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Sort
        if ($sortField === 'posts_count') {
            $query->orderBy('posts_count', $sortDirection);
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        $tags = $query->paginate(20)
            ->withQueryString()
            ->through(fn (Tag $tag) => [
                'id' => $tag->id,
                'uuid' => $tag->uuid,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'posts_count' => $tag->posts_count,
                'created_at' => $tag->created_at,
            ]);

        return Inertia::render('Tags/Index', [
            'tags' => $tags,
            'filters' => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tags/Create');
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Tag::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? null,
        ]);

        return redirect()->route('cms.tags.index')
            ->with('success', 'Tag created successfully.');
    }

    public function edit(Tag $tag): Response
    {
        return Inertia::render('Tags/Edit', [
            'tag' => [
                'id' => $tag->id,
                'uuid' => $tag->uuid,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'posts_count' => $tag->posts_count,
                'created_at' => $tag->created_at,
            ],
        ]);
    }

    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $validated = $request->validated();

        $tag->update([
            'name' => $validated['name'],
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
