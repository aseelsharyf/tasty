<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\UpdateHomepageLayoutRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\Layouts\HomepageConfigurationService;
use App\Services\Layouts\SectionCategoryMappingService;
use App\Services\Layouts\SectionRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LayoutController extends Controller
{
    public function __construct(
        protected SectionRegistry $registry,
        protected HomepageConfigurationService $configService,
        protected SectionCategoryMappingService $mappingService
    ) {}

    /**
     * Show the homepage layout editor.
     */
    public function homepage(): Response
    {
        $configuration = $this->configService->getConfiguration();
        $sectionTypes = $this->registry->toArray();

        return Inertia::render('Layouts/Homepage', [
            'configuration' => $configuration,
            'sectionTypes' => $sectionTypes,
        ]);
    }

    /**
     * Update the homepage layout configuration.
     */
    public function updateHomepage(UpdateHomepageLayoutRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->configService->saveConfiguration(
            ['sections' => $validated['sections']],
            $request->user()->id
        );

        return redirect()->route('cms.layouts.homepage')
            ->with('success', 'Homepage layout updated successfully.');
    }

    /**
     * Search posts for slot assignment.
     */
    public function searchPosts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:50'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sectionType' => ['nullable', 'string', 'max:50'],
        ]);

        $query = Post::query()
            ->with(['featuredMedia', 'categories'])
            ->published()
            ->latest('published_at');

        if (! empty($validated['query'])) {
            $searchTerm = $validated['query'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('kicker', 'like', "%{$searchTerm}%")
                    ->orWhere('excerpt', 'like', "%{$searchTerm}%");
            });
        }

        if (! empty($validated['type'])) {
            $query->where('post_type', $validated['type']);
        }

        // Apply section category restrictions
        if (! empty($validated['sectionType'])) {
            $allowedCategoryIds = $this->mappingService->getAllowedCategoryIds($validated['sectionType']);

            if ($allowedCategoryIds !== null) {
                $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $allowedCategoryIds));
            }
        }

        $posts = $query->limit($validated['limit'] ?? 20)->get();

        return response()->json([
            'posts' => $posts->map(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'kicker' => $post->kicker,
                'excerpt' => $post->excerpt,
                'image' => $post->featured_image_thumb,
                'category' => $post->categories->first()?->name,
                'categoryIds' => $post->categories->pluck('id')->toArray(),
                'postType' => $post->post_type,
                'publishedAt' => $post->published_at?->format('M j, Y'),
            ]),
        ]);
    }

    /**
     * Get available section types.
     */
    public function getSectionTypes(): JsonResponse
    {
        return response()->json([
            'sectionTypes' => $this->registry->toArray(),
        ]);
    }

    /**
     * Get a single post for display.
     */
    public function getPost(Post $post): JsonResponse
    {
        $post->load(['featuredMedia', 'categories', 'author']);

        return response()->json([
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'kicker' => $post->kicker,
                'excerpt' => $post->excerpt,
                'image' => $post->featured_image_thumb,
                'category' => $post->categories->first()?->name,
                'author' => $post->author?->name,
                'postType' => $post->post_type,
                'publishedAt' => $post->published_at?->format('M j, Y'),
            ],
        ]);
    }

    /**
     * Get multiple posts by IDs for batch loading.
     */
    public function getPostsBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'string'],
        ]);

        $ids = array_filter(array_map('intval', explode(',', $validated['ids'])));

        if (empty($ids)) {
            return response()->json([]);
        }

        $posts = Post::query()
            ->with(['featuredMedia', 'categories'])
            ->whereIn('id', $ids)
            ->get();

        return response()->json(
            $posts->map(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'kicker' => $post->kicker,
                'excerpt' => $post->excerpt,
                'image' => $post->featured_image_thumb,
                'category' => $post->categories->first()?->name,
                'postType' => $post->post_type,
                'publishedAt' => $post->published_at?->format('M j, Y'),
            ])
        );
    }

    /**
     * Get all tags for dropdown selection.
     */
    public function getTags(): JsonResponse
    {
        $tags = Tag::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'tags' => $tags->map(fn (Tag $tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ]),
        ]);
    }

    /**
     * Get all categories for dropdown selection.
     */
    public function getCategories(): JsonResponse
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('order')
            ->get();

        // Flatten tree with depth indicator
        $flatten = function ($items, $depth = 0) use (&$flatten) {
            $result = [];
            foreach ($items as $item) {
                $result[] = [
                    'id' => $item->id,
                    'name' => $depth > 0 ? str_repeat('─', $depth).' '.$item->name : $item->name,
                    'slug' => $item->slug,
                ];
                if ($item->children->isNotEmpty()) {
                    $result = array_merge($result, $flatten($item->children, $depth + 1));
                }
            }

            return $result;
        };

        return response()->json([
            'categories' => $flatten($categories),
        ]);
    }
}
