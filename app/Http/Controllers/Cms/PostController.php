<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StorePostRequest;
use App\Http\Requests\Cms\UpdatePostRequest;
use App\Models\Category;
use App\Models\Language;
use App\Models\Post;
use App\Models\PostEditLock;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use App\Services\PostTemplateRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function index(Request $request, string $language): Response
    {
        // Normalize and validate language (case-insensitive)
        $language = strtolower($language);
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

        /** @var User $user */
        $user = Auth::user();
        $isEditorOrAdmin = $user->hasAnyRole(['Admin', 'Editor', 'Developer']);

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

        // Role-based filtering:
        // - Draft: Writers see only their own drafts, Editors/Admins see all
        // - Pending: Writers see only their own, Editors/Admins see all
        // - Published: Everyone can see (read-only for non-authors/non-editors)
        if (! $isEditorOrAdmin && in_array($status, ['draft', 'pending'])) {
            $query->where('author_id', $user->id);
        }

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
                'workflow_status' => $post->workflow_status ?? 'draft',
            ]);

        // Get counts for status tabs (filtered by language and user role)
        $baseQuery = fn () => Post::where('language_code', $language);

        if ($isEditorOrAdmin) {
            // Editors and Admins see all posts
            $counts = [
                'all' => $baseQuery()->withoutTrashed()->count(),
                'draft' => $baseQuery()->draft()->count(),
                'pending' => $baseQuery()->pending()->count(),
                'published' => $baseQuery()->where('status', Post::STATUS_PUBLISHED)->count(),
                'scheduled' => $baseQuery()->where('status', Post::STATUS_SCHEDULED)->count(),
                'trashed' => $baseQuery()->onlyTrashed()->count(),
            ];
        } else {
            // Writers see their own drafts/pending, but all published posts
            $counts = [
                'all' => $baseQuery()->withoutTrashed()->where(function ($q) use ($user) {
                    $q->where('author_id', $user->id)
                        ->orWhere('status', Post::STATUS_PUBLISHED);
                })->count(),
                'draft' => $baseQuery()->draft()->where('author_id', $user->id)->count(),
                'pending' => $baseQuery()->pending()->where('author_id', $user->id)->count(),
                'published' => $baseQuery()->where('status', Post::STATUS_PUBLISHED)->count(),
                'scheduled' => $baseQuery()->where('status', Post::STATUS_SCHEDULED)->where('author_id', $user->id)->count(),
                'trashed' => $baseQuery()->onlyTrashed()->where('author_id', $user->id)->count(),
            ];
        }

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
            'userCapabilities' => [
                'isEditorOrAdmin' => $isEditorOrAdmin,
                'userId' => $user->id,
            ],
        ]);
    }

    public function create(string $language): Response
    {
        $language = strtolower($language);
        $lang = Language::where('code', $language)->firstOrFail();

        // Set locale for translatable models
        app()->setLocale($language);

        $categories = Category::whereNull('parent_id')
            ->with('children')
            ->orderBy('order')
            ->get()
            ->map(fn ($cat) => $this->formatCategoryForSelect($cat));

        $tags = Tag::orderByTranslatedName($language)->get()->map(fn ($tag) => $this->formatTagForSelect($tag));

        // Get post types from settings
        $postTypes = collect(Setting::getPostTypes())->map(fn ($type) => [
            'value' => $type['slug'],
            'label' => $type['name'],
            'icon' => $type['icon'] ?? null,
            'fields' => $type['fields'] ?? [],
        ])->values()->all();

        return Inertia::render('Posts/Create', [
            'categories' => $categories,
            'tags' => $tags,
            'postTypes' => $postTypes,
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
        $language = strtolower($language);
        $validated = $request->validated();

        // Validate language code
        if (! Language::isValidCode($language)) {
            abort(404, 'Invalid language code');
        }

        // Create post with draft status initially (workflow controls publishing)
        $post = Post::create([
            'author_id' => Auth::id(),
            'language_code' => $language,
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'slug' => $validated['slug'] ?? null,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'] ?? null,
            'post_type' => $validated['post_type'] ?? Post::TYPE_ARTICLE,
            'status' => Post::STATUS_DRAFT, // Always start as draft
            'workflow_status' => 'draft',
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'featured_media_id' => $validated['featured_media_id'] ?? null,
            'custom_fields' => $validated['custom_fields'] ?? null,
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

        // Create initial version using HasWorkflow trait method
        $post->createVersion(null, 'Initial version');

        // Redirect to edit page so user can use workflow
        return redirect()->route('cms.posts.edit', ['language' => $language, 'post' => $post])
            ->with('success', 'Post created. Use the workflow panel to submit for review.');
    }

    public function show(string $language, Post $post): Response
    {
        return $this->edit($language, $post);
    }

    public function edit(Request $request, string $language, Post $post): Response
    {
        $language = strtolower($language);
        $post->load(['categories', 'tags', 'author', 'language', 'featuredMedia', 'draftVersion', 'activeVersion', 'versions.createdBy']);

        // Set locale for translatable models
        app()->setLocale($language);

        // Try to acquire edit lock
        $user = Auth::user();
        $lock = PostEditLock::tryAcquire($post, $user);
        $lockInfo = PostEditLock::getLockInfo($post);
        $canEdit = $lock !== null || ($lockInfo && $lockInfo['is_stale']);

        $categories = Category::whereNull('parent_id')
            ->with('children')
            ->orderBy('order')
            ->get()
            ->map(fn ($cat) => $this->formatCategoryForSelect($cat));

        $tags = Tag::orderByTranslatedName($language)->get()->map(fn ($tag) => $this->formatTagForSelect($tag));

        $lang = $post->language ?? Language::getDefault();

        // Get workflow configuration for this post type
        $workflowConfig = Setting::getWorkflow($post->post_type);

        // Helper to check if a version belongs to this post
        $isVersionValid = fn ($version) => $version
            && $version->versionable_type === Post::class
            && $version->versionable_id === $post->id;

        // Get valid draft and active versions (verify they belong to this post)
        $validDraftVersion = $isVersionValid($post->draftVersion) ? $post->draftVersion : null;
        $validActiveVersion = $isVersionValid($post->activeVersion) ? $post->activeVersion : null;

        // Get current version UUID - check query param first, then prefer draft version, fall back to active version
        $requestedVersionUuid = $request->query('version');
        $currentVersionUuid = $requestedVersionUuid
            ?? $validDraftVersion?->uuid
            ?? $validActiveVersion?->uuid
            ?? $post->versions()->latest()->first()?->uuid;

        // Validate the requested version belongs to this post
        $currentVersion = null;
        if ($requestedVersionUuid) {
            $currentVersion = $post->versions()->where('uuid', $requestedVersionUuid)->first();
            if (! $currentVersion) {
                $currentVersionUuid = $validDraftVersion?->uuid
                    ?? $validActiveVersion?->uuid
                    ?? $post->versions()->latest()->first()?->uuid;
                $currentVersion = $post->versions()->where('uuid', $currentVersionUuid)->first();
            }
        } else {
            // Load the current version for content snapshot
            $currentVersion = $post->versions()->where('uuid', $currentVersionUuid)->first();
        }

        // Get content from version snapshot if viewing a specific version
        $versionSnapshot = $currentVersion?->content_snapshot ?? [];

        // Get list of versions for the version switcher dropdown
        $versionsList = $post->versions()
            ->orderByDesc('version_number')
            ->get()
            ->map(fn ($v) => [
                'uuid' => $v->uuid,
                'version_number' => $v->version_number,
                'workflow_status' => $v->workflow_status,
                'is_active' => $v->is_active,
                'is_current' => $v->uuid === $currentVersionUuid,
                // is_draft should reflect the actual workflow status, not just whether it's the draft_version_id
                'is_draft' => $v->workflow_status === 'draft',
                'created_by' => $v->createdBy ? [
                    'name' => $v->createdBy->name,
                ] : null,
                'created_at' => $v->created_at->toIso8601String(),
            ]);

        // Get post types from settings with their custom fields
        $postTypes = collect(Setting::getPostTypes())->map(fn ($type) => [
            'value' => $type['slug'],
            'label' => $type['name'],
            'icon' => $type['icon'] ?? null,
            'fields' => $type['fields'] ?? [],
        ])->values()->all();

        // Find the current post type config for custom fields
        $currentPostTypeConfig = collect(Setting::getPostTypes())
            ->firstWhere('slug', $post->post_type);

        // When viewing a specific version, use its snapshot for content fields
        // But keep using post model for metadata (status, published_at, etc.)
        $useSnapshot = ! empty($versionSnapshot);

        return Inertia::render('Posts/Edit', [
            'post' => [
                'id' => $post->id,
                'uuid' => $post->uuid,
                // Content fields - use snapshot if available
                'title' => $useSnapshot ? ($versionSnapshot['title'] ?? $post->title) : $post->title,
                'subtitle' => $useSnapshot ? ($versionSnapshot['subtitle'] ?? $post->subtitle) : $post->subtitle,
                'slug' => $useSnapshot ? ($versionSnapshot['slug'] ?? $post->slug) : $post->slug,
                'excerpt' => $useSnapshot ? ($versionSnapshot['excerpt'] ?? $post->excerpt) : $post->excerpt,
                'content' => $useSnapshot ? ($versionSnapshot['content'] ?? $post->content) : $post->content,
                'post_type' => $useSnapshot ? ($versionSnapshot['post_type'] ?? $post->post_type) : $post->post_type,
                'template' => $useSnapshot ? ($versionSnapshot['template'] ?? $post->template) : $post->template,
                'custom_fields' => $useSnapshot ? ($versionSnapshot['custom_fields'] ?? $post->custom_fields) : $post->custom_fields,
                'meta_title' => $useSnapshot ? ($versionSnapshot['meta_title'] ?? $post->meta_title) : $post->meta_title,
                'meta_description' => $useSnapshot ? ($versionSnapshot['meta_description'] ?? $post->meta_description) : $post->meta_description,
                'featured_media_id' => $useSnapshot ? ($versionSnapshot['featured_media_id'] ?? $post->featured_media_id) : $post->featured_media_id,
                // Metadata fields - always from post model
                'status' => $post->status,
                'workflow_status' => $currentVersion?->workflow_status ?? $post->workflow_status ?? 'draft',
                'published_at' => $post->published_at,
                'scheduled_at' => $post->scheduled_at,
                'featured_image_url' => $post->featured_image_url,
                'featured_image_thumb' => $post->featured_image_thumb,
                'featured_media' => $post->featuredMedia ? [
                    'id' => $post->featuredMedia->id,
                    'uuid' => $post->featuredMedia->uuid,
                    'type' => $post->featuredMedia->type,
                    'url' => $post->featuredMedia->url,
                    'thumbnail_url' => $post->featuredMedia->thumbnail_url,
                    'title' => $post->featuredMedia->title,
                    'alt_text' => $post->featuredMedia->alt_text,
                    'caption' => $post->featuredMedia->caption,
                    'credit_display' => $post->featuredMedia->credit_display,
                    'is_image' => $post->featuredMedia->is_image,
                    'is_video' => $post->featuredMedia->is_video,
                ] : null,
                // Taxonomy - use snapshot if available
                'category_id' => $useSnapshot
                    ? ($versionSnapshot['category_ids'][0] ?? $post->categories->first()?->id)
                    : $post->categories->first()?->id,
                'tags' => $useSnapshot
                    ? ($versionSnapshot['tag_ids'] ?? $post->tags->pluck('id'))
                    : $post->tags->pluck('id'),
                'language_code' => $post->language_code,
                'author' => $post->author ? [
                    'id' => $post->author->id,
                    'name' => $post->author->name,
                ] : null,
                'show_author' => $useSnapshot ? ($versionSnapshot['show_author'] ?? $post->show_author) : $post->show_author,
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
                'current_version_uuid' => $currentVersionUuid,
            ],
            'categories' => $categories,
            'tags' => $tags,
            'postTypes' => $postTypes,
            'currentPostType' => $currentPostTypeConfig ? [
                'key' => $currentPostTypeConfig['slug'],
                'label' => $currentPostTypeConfig['name'],
                'icon' => $currentPostTypeConfig['icon'] ?? null,
                'fields' => $currentPostTypeConfig['fields'] ?? [],
            ] : null,
            'language' => $lang ? [
                'code' => $lang->code,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'direction' => $lang->direction,
                'is_rtl' => $lang->isRtl(),
            ] : null,
            'workflowConfig' => $workflowConfig,
            'versionsList' => $versionsList,
            'editLock' => [
                'canEdit' => $canEdit,
                'lock' => $lockInfo,
                'isMine' => $lockInfo && $lockInfo['user_id'] === $user->id,
                'heartbeatInterval' => PostEditLock::HEARTBEAT_INTERVAL_SECONDS * 1000, // in ms
            ],
            'templates' => PostTemplateRegistry::forSelect(),
        ]);
    }

    public function update(UpdatePostRequest $request, string $language, Post $post): RedirectResponse
    {
        $validated = $request->validated();

        // Check if the post is currently published
        // If published, we only update the draft version's snapshot - NOT the post model
        // This prevents changes from affecting the live published content
        $isPublished = $post->status === Post::STATUS_PUBLISHED;

        if ($isPublished) {
            // Post is published - only update the draft version's snapshot
            // Build content snapshot from the validated data (not from post model)
            $contentSnapshot = [
                'title' => $validated['title'],
                'subtitle' => $validated['subtitle'] ?? null,
                'excerpt' => $validated['excerpt'] ?? null,
                'content' => $validated['content'] ?? null,
                'template' => $validated['template'] ?? $post->template,
                'meta_title' => $validated['meta_title'] ?? null,
                'meta_description' => $validated['meta_description'] ?? null,
                'featured_media_id' => $validated['featured_media_id'] ?? null,
                'custom_fields' => $validated['custom_fields'] ?? null,
                'allow_comments' => $validated['allow_comments'] ?? true,
                'show_author' => $validated['show_author'] ?? true,
                'category_ids' => ! empty($validated['category_id']) ? [$validated['category_id']] : [],
                'tag_ids' => $validated['tags'] ?? [],
            ];

            // Update only the draft version, not the post model
            $post->updateDraftVersion($contentSnapshot);

            return redirect()->route('cms.posts.edit', ['language' => $language, 'post' => $post])
                ->with('success', 'Draft saved. Changes will apply when published.');
        }

        // Post is not published - update the post model directly
        $post->update([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'slug' => $validated['slug'] ?? $post->slug,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'] ?? null,
            'post_type' => $validated['post_type'] ?? $post->post_type,
            'template' => $validated['template'] ?? $post->template,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'featured_media_id' => $validated['featured_media_id'] ?? null,
            'custom_fields' => $validated['custom_fields'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'show_author' => $validated['show_author'] ?? $post->show_author,
        ]);

        // Sync category and tags on the post model
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

        // Build content snapshot for versioning
        $contentSnapshot = $post->buildContentSnapshot();

        // Update existing draft version or create new one if needed
        $post->updateDraftVersion($contentSnapshot);

        return redirect()->route('cms.posts.edit', ['language' => $language, 'post' => $post])
            ->with('success', 'Post saved.');
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
