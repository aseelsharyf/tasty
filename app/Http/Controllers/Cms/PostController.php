<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StorePostRequest;
use App\Http\Requests\Cms\UpdatePostRequest;
use App\Models\Category;
use App\Models\Language;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function index(Request $request, string $language): Response
    {
        // Validate language
        $lang = Language::where('code', $language)->where('is_active', true)->firstOrFail();

        // Set locale for translatable models
        app()->setLocale($language);

        $status = $request->get('status', 'all');
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $allowedSorts = ['title', 'author', 'status', 'post_type', 'published_at', 'created_at'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }

        $query = Post::query()
            ->where('language_code', $language)
            ->with([
                'author' => fn ($q) => $q->select('id', 'name')->with('media'),
                'categories:id,name,slug',
                'tags:id,name,slug',
            ]);

        // Filter by status
        match ($status) {
            'draft' => $query->draft(),
            'pending' => $query->pending(),
            'published' => $query->where('status', Post::STATUS_PUBLISHED),
            'scheduled' => $query->where('status', Post::STATUS_SCHEDULED),
            'trashed' => $query->onlyTrashed(),
            default => $query->withoutTrashed(),
        };

        // Filter by post type
        if ($request->filled('post_type')) {
            $query->ofType($request->get('post_type'));
        }

        // Filter by author
        if ($request->filled('author')) {
            $query->where('author_id', $request->get('author'));
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->get('category'));
            });
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        // Sort
        if ($sortField === 'author') {
            $query->join('users', 'posts.author_id', '=', 'users.id')
                ->orderBy('users.name', $sortDirection)
                ->select('posts.*');
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        $posts = $query->paginate(15)
            ->withQueryString()
            ->through(fn (Post $post) => [
                'id' => $post->id,
                'uuid' => $post->uuid,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'post_type' => $post->post_type,
                'status' => $post->status,
                'author' => $post->author ? [
                    'id' => $post->author->id,
                    'name' => $post->author->name,
                    'avatar_url' => $post->author->avatar_url,
                ] : null,
                'categories' => $post->categories->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                ]),
                'tags' => $post->tags->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'slug' => $t->slug,
                ]),
                'featured_image_thumb' => $post->featured_image_thumb,
                'published_at' => $post->published_at,
                'scheduled_at' => $post->scheduled_at,
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
                'language_code' => $post->language_code,
                'deleted_at' => $post->deleted_at,
            ]);

        // Get counts for status tabs (filtered by language)
        $counts = [
            'all' => Post::where('language_code', $language)->withoutTrashed()->count(),
            'draft' => Post::where('language_code', $language)->draft()->count(),
            'pending' => Post::where('language_code', $language)->pending()->count(),
            'published' => Post::where('language_code', $language)->where('status', Post::STATUS_PUBLISHED)->count(),
            'scheduled' => Post::where('language_code', $language)->where('status', Post::STATUS_SCHEDULED)->count(),
            'trashed' => Post::where('language_code', $language)->onlyTrashed()->count(),
        ];

        // Get filter options
        $authors = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Admin', 'Developer', 'Writer', 'Editor']))
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = Category::orderByTranslatedName($language)->get()
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->getTranslation('name', $language, false) ?: $c->getTranslation('name', 'en'),
            ]);

        return Inertia::render('Posts/Index', [
            'posts' => $posts,
            'counts' => $counts,
            'authors' => $authors,
            'categories' => $categories,
            'filters' => $request->only(['status', 'search', 'post_type', 'author', 'category', 'sort', 'direction']),
            'language' => [
                'code' => $lang->code,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'direction' => $lang->direction,
                'is_rtl' => $lang->isRtl(),
            ],
        ]);
    }

    public function create(string $language): Response
    {
        $lang = Language::where('code', $language)->firstOrFail();

        // Set locale for translatable models
        app()->setLocale($language);

        $categories = Category::whereNull('parent_id')
            ->with('children')
            ->orderBy('order')
            ->get()
            ->map(fn ($cat) => $this->formatCategoryForSelect($cat));

        $tags = Tag::orderByTranslatedName($language)->get()->map(fn ($tag) => $this->formatTagForSelect($tag));

        return Inertia::render('Posts/Create', [
            'categories' => $categories,
            'tags' => $tags,
            'postTypes' => [
                ['value' => Post::TYPE_ARTICLE, 'label' => 'Article'],
                ['value' => Post::TYPE_RECIPE, 'label' => 'Recipe'],
            ],
            'language' => [
                'code' => $lang->code,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'direction' => $lang->direction,
                'is_rtl' => $lang->isRtl(),
            ],
        ]);
    }

    public function store(StorePostRequest $request, string $language): RedirectResponse
    {
        $validated = $request->validated();

        // Validate language code
        if (! Language::isValidCode($language)) {
            abort(404, 'Invalid language code');
        }

        $post = Post::create([
            'author_id' => Auth::id(),
            'language_code' => $language,
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'slug' => $validated['slug'] ?? null,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'] ?? null,
            'post_type' => $validated['post_type'] ?? Post::TYPE_ARTICLE,
            'status' => $validated['status'] ?? Post::STATUS_DRAFT,
            'published_at' => $validated['status'] === Post::STATUS_PUBLISHED ? now() : null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'recipe_meta' => $validated['recipe_meta'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ]);

        // Sync category and tags
        if (! empty($validated['category_id'])) {
            $post->categories()->sync([$validated['category_id']]);
        } else {
            $post->categories()->detach();
        }

        if (! empty($validated['tags'])) {
            $post->tags()->sync($validated['tags']);
        }

        // Handle featured image
        if ($request->hasFile('featured_image')) {
            $post->addMediaFromRequest('featured_image')
                ->toMediaCollection('featured');
        }

        $message = $post->status === Post::STATUS_PUBLISHED
            ? 'Post published successfully.'
            : 'Post saved as draft.';

        return redirect()->route('cms.posts.index', ['language' => $language])
            ->with('success', $message);
    }

    public function show(string $language, Post $post): Response
    {
        return $this->edit($language, $post);
    }

    public function edit(string $language, Post $post): Response
    {
        $post->load(['categories', 'tags', 'author', 'language']);

        // Set locale for translatable models
        app()->setLocale($language);

        $categories = Category::whereNull('parent_id')
            ->with('children')
            ->orderBy('order')
            ->get()
            ->map(fn ($cat) => $this->formatCategoryForSelect($cat));

        $tags = Tag::orderByTranslatedName($language)->get()->map(fn ($tag) => $this->formatTagForSelect($tag));

        $lang = $post->language ?? Language::getDefault();

        return Inertia::render('Posts/Edit', [
            'post' => [
                'id' => $post->id,
                'uuid' => $post->uuid,
                'title' => $post->title,
                'subtitle' => $post->subtitle,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'post_type' => $post->post_type,
                'status' => $post->status,
                'published_at' => $post->published_at,
                'scheduled_at' => $post->scheduled_at,
                'recipe_meta' => $post->recipe_meta,
                'meta_title' => $post->meta_title,
                'meta_description' => $post->meta_description,
                'featured_image_url' => $post->featured_image_url,
                'featured_image_thumb' => $post->featured_image_thumb,
                'category_id' => $post->categories->first()?->id,
                'tags' => $post->tags->pluck('id'),
                'language_code' => $post->language_code,
                'author' => $post->author ? [
                    'id' => $post->author->id,
                    'name' => $post->author->name,
                ] : null,
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ],
            'categories' => $categories,
            'tags' => $tags,
            'postTypes' => [
                ['value' => Post::TYPE_ARTICLE, 'label' => 'Article'],
                ['value' => Post::TYPE_RECIPE, 'label' => 'Recipe'],
            ],
            'language' => $lang ? [
                'code' => $lang->code,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'direction' => $lang->direction,
                'is_rtl' => $lang->isRtl(),
            ] : null,
        ]);
    }

    public function update(UpdatePostRequest $request, string $language, Post $post): RedirectResponse
    {
        $validated = $request->validated();

        $wasPublished = $post->isPublished();

        $post->update([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'slug' => $validated['slug'] ?? $post->slug,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'] ?? null,
            'post_type' => $validated['post_type'] ?? $post->post_type,
            'status' => $validated['status'] ?? $post->status,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'recipe_meta' => $validated['recipe_meta'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ]);

        // Set published_at if just published
        if (! $wasPublished && $post->status === Post::STATUS_PUBLISHED) {
            $post->update(['published_at' => now()]);
        }

        // Sync category and tags
        if (! empty($validated['category_id'])) {
            $post->categories()->sync([$validated['category_id']]);
        } else {
            $post->categories()->detach();
        }
        $post->tags()->sync($validated['tags'] ?? []);

        // Handle featured image
        if ($request->hasFile('featured_image')) {
            $post->addMediaFromRequest('featured_image')
                ->toMediaCollection('featured');
        } elseif ($request->boolean('remove_featured_image')) {
            $post->clearMediaCollection('featured');
        }

        return redirect()->route('cms.posts.edit', ['language' => $language, 'post' => $post])
            ->with('success', 'Post updated successfully.');
    }

    public function destroy(string $language, Post $post): RedirectResponse
    {
        $post->delete();

        return redirect()->route('cms.posts.index', ['language' => $language])
            ->with('success', 'Post moved to trash.');
    }

    public function publish(string $language, Post $post): RedirectResponse
    {
        $post->publish();

        return redirect()->back()
            ->with('success', 'Post published successfully.');
    }

    public function unpublish(string $language, Post $post): RedirectResponse
    {
        $post->unpublish();

        return redirect()->back()
            ->with('success', 'Post unpublished.');
    }

    public function restore(string $language, string $uuid): RedirectResponse
    {
        $post = Post::withTrashed()->where('uuid', $uuid)->firstOrFail();
        $post->restore();

        return redirect()->route('cms.posts.index', ['language' => $language])
            ->with('success', 'Post restored successfully.');
    }

    public function forceDelete(string $language, string $uuid): RedirectResponse
    {
        $post = Post::withTrashed()->where('uuid', $uuid)->firstOrFail();
        $post->forceDelete();

        return redirect()->route('cms.posts.index', ['language' => $language, 'status' => 'trashed'])
            ->with('success', 'Post permanently deleted.');
    }

    private function formatCategoryForSelect(Category $category, int $depth = 0): array
    {
        $prefix = str_repeat('— ', $depth);

        $result = [
            'id' => $category->id,
            'uuid' => $category->uuid,
            'name' => $prefix.$category->name,
            'slug' => $category->slug,
            'depth' => $depth,
            'translations' => $category->getTranslations('name'),
        ];

        if ($category->children && $category->children->count() > 0) {
            $result['children'] = $category->children->map(
                fn ($child) => $this->formatCategoryForSelect($child, $depth + 1)
            )->all();
        }

        return $result;
    }

    private function formatTagForSelect(Tag $tag): array
    {
        return [
            'id' => $tag->id,
            'uuid' => $tag->uuid,
            'name' => $tag->name,
            'slug' => $tag->slug,
            'translations' => $tag->getTranslations('name'),
        ];
    }
}
