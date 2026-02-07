<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\UpdateHomepageLayoutRequest;
use App\Http\Requests\Cms\UpdatePageLayoutRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
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
     * Show the layouts index page with all configured layouts.
     */
    public function index(): Response
    {
        // Get categories with custom layouts
        $categoriesWithLayouts = Category::query()
            ->whereHas('pageLayout')
            ->with('pageLayout')
            ->orderByTranslatedName(app()->getLocale())
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'uuid' => $category->uuid,
                'name' => $category->name,
                'slug' => $category->slug,
                'sectionsCount' => count($category->pageLayout?->configuration['sections'] ?? []),
                'updatedAt' => $category->pageLayout?->updated_at?->format('M j, Y'),
            ]);

        // Get tags with custom layouts
        $tagsWithLayouts = Tag::query()
            ->whereHas('pageLayout')
            ->with('pageLayout')
            ->orderByTranslatedName(app()->getLocale())
            ->get()
            ->map(fn (Tag $tag) => [
                'id' => $tag->id,
                'uuid' => $tag->uuid,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'sectionsCount' => count($tag->pageLayout?->configuration['sections'] ?? []),
                'updatedAt' => $tag->pageLayout?->updated_at?->format('M j, Y'),
            ]);

        // Get all categories and tags for quick access
        $allCategories = Category::query()
            ->orderByTranslatedName(app()->getLocale())
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'uuid' => $category->uuid,
                'name' => $category->name,
                'slug' => $category->slug,
            ]);

        $allTags = Tag::query()
            ->orderByTranslatedName(app()->getLocale())
            ->get()
            ->map(fn (Tag $tag) => [
                'id' => $tag->id,
                'uuid' => $tag->uuid,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ]);

        return Inertia::render('Layouts/Index', [
            'categoriesWithLayouts' => $categoriesWithLayouts,
            'tagsWithLayouts' => $tagsWithLayouts,
            'allCategories' => $allCategories,
            'allTags' => $allTags,
        ]);
    }

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
            'excludeIds' => ['nullable', 'string'], // Comma-separated list of IDs to exclude
            'manual' => ['nullable', 'boolean'], // Skip reserved-category exclusion for manual selection
        ]);

        $query = Post::query()
            ->with(['featuredMedia', 'categories'])
            ->published()
            ->latest('published_at');

        // Exclude specific post IDs (for preview deduplication)
        if (! empty($validated['excludeIds'])) {
            $excludeIds = array_filter(array_map('intval', explode(',', $validated['excludeIds'])));
            if (! empty($excludeIds)) {
                $query->whereNotIn('id', $excludeIds);
            }
        }

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
                // Section has specific categories - only show posts from those categories
                $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $allowedCategoryIds));
            } elseif (empty($validated['manual'])) {
                // For dynamic auto-fill only: exclude posts from categories reserved by other sections.
                // Manual selection skips this so users can pick any post.
                $reservedCategoryIds = $this->mappingService->getCategoryIdsReservedByOtherSections($validated['sectionType']);

                if (! empty($reservedCategoryIds)) {
                    $query->whereDoesntHave('categories', fn ($q) => $q->whereIn('categories.id', $reservedCategoryIds));
                }
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
            ->orderByTranslatedName(app()->getLocale())
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

    /**
     * Search products for slot assignment.
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Product::query()
            ->with(['featuredMedia', 'category'])
            ->active()
            ->ordered();

        if (! empty($validated['query'])) {
            $searchTerm = $validated['query'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('brand', 'like', "%{$searchTerm}%")
                    ->orWhere('short_description', 'like', "%{$searchTerm}%");
            });
        }

        $products = $query->limit($validated['limit'] ?? 20)->get();

        return response()->json([
            'products' => $products->map(fn (Product $product) => [
                'id' => $product->id,
                'title' => $product->title,
                'brand' => $product->brand,
                'shortDescription' => $product->short_description,
                'image' => $product->featured_image_url,
                'category' => $product->category?->name,
                'price' => $product->formatted_price,
                'availability' => $product->availability,
            ]),
        ]);
    }

    /**
     * Get a single product for display.
     */
    public function getProduct(Product $product): JsonResponse
    {
        $product->load(['featuredMedia', 'category']);

        return response()->json([
            'product' => [
                'id' => $product->id,
                'title' => $product->title,
                'brand' => $product->brand,
                'shortDescription' => $product->short_description,
                'image' => $product->featured_image_url,
                'category' => $product->category?->name,
                'price' => $product->formatted_price,
                'availability' => $product->availability,
            ],
        ]);
    }

    /**
     * Get multiple products by IDs for batch loading.
     */
    public function getProductsBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'string'],
        ]);

        $ids = array_filter(array_map('intval', explode(',', $validated['ids'])));

        if (empty($ids)) {
            return response()->json([]);
        }

        $products = Product::query()
            ->with(['featuredMedia', 'category'])
            ->whereIn('id', $ids)
            ->get();

        return response()->json(
            $products->map(fn (Product $product) => [
                'id' => $product->id,
                'title' => $product->title,
                'brand' => $product->brand,
                'shortDescription' => $product->short_description,
                'image' => $product->featured_image_url,
                'category' => $product->category?->name,
                'price' => $product->formatted_price,
                'availability' => $product->availability,
            ])
        );
    }

    /**
     * Show the category layout editor.
     */
    public function categoryLayout(Category $category): Response
    {
        $category->load('pageLayout');

        $hasCustomLayout = $category->pageLayout !== null;
        $useCustomLayout = $hasCustomLayout && ($category->pageLayout->configuration['enabled'] ?? true);
        $configuration = $category->getLayoutConfiguration() ?? $this->getDefaultCategoryConfiguration($category);
        $sectionTypes = $this->registry->toArray();

        return Inertia::render('Layouts/CategoryLayout', [
            'category' => [
                'id' => $category->id,
                'uuid' => $category->uuid,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
            'configuration' => $configuration,
            'useCustomLayout' => $useCustomLayout,
            'hasExistingLayout' => $hasCustomLayout,
            'sectionTypes' => $sectionTypes,
        ]);
    }

    /**
     * Update the category layout configuration.
     */
    public function updateCategoryLayout(UpdatePageLayoutRequest $request, Category $category): RedirectResponse
    {
        $validated = $request->validated();
        $useCustomLayout = $request->boolean('useCustomLayout', true);

        $category->pageLayout()->updateOrCreate(
            ['layoutable_type' => Category::class, 'layoutable_id' => $category->id],
            [
                'configuration' => [
                    'enabled' => $useCustomLayout,
                    'sections' => $validated['sections'],
                ],
                'version' => ($category->pageLayout?->version ?? 0) + 1,
                'updated_by' => $request->user()->id,
            ]
        );

        return redirect()->route('cms.layouts.category', $category)
            ->with('success', 'Category layout updated successfully.');
    }

    /**
     * Show the tag layout editor.
     */
    public function tagLayout(Tag $tag): Response
    {
        $tag->load('pageLayout');

        $hasCustomLayout = $tag->pageLayout !== null;
        $useCustomLayout = $hasCustomLayout && ($tag->pageLayout->configuration['enabled'] ?? true);
        $configuration = $tag->getLayoutConfiguration() ?? $this->getDefaultTagConfiguration($tag);
        $sectionTypes = $this->registry->toArray();

        return Inertia::render('Layouts/TagLayout', [
            'tag' => [
                'id' => $tag->id,
                'uuid' => $tag->uuid,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ],
            'configuration' => $configuration,
            'useCustomLayout' => $useCustomLayout,
            'hasExistingLayout' => $hasCustomLayout,
            'sectionTypes' => $sectionTypes,
        ]);
    }

    /**
     * Update the tag layout configuration.
     */
    public function updateTagLayout(UpdatePageLayoutRequest $request, Tag $tag): RedirectResponse
    {
        $validated = $request->validated();
        $useCustomLayout = $request->boolean('useCustomLayout', true);

        $tag->pageLayout()->updateOrCreate(
            ['layoutable_type' => Tag::class, 'layoutable_id' => $tag->id],
            [
                'configuration' => [
                    'enabled' => $useCustomLayout,
                    'sections' => $validated['sections'],
                ],
                'version' => ($tag->pageLayout?->version ?? 0) + 1,
                'updated_by' => $request->user()->id,
            ]
        );

        return redirect()->route('cms.layouts.tag', $tag)
            ->with('success', 'Tag layout updated successfully.');
    }

    /**
     * Get default layout configuration for a category.
     *
     * @return array<string, mixed>
     */
    protected function getDefaultCategoryConfiguration(Category $category): array
    {
        return [
            'sections' => [
                [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'type' => 'latest-updates',
                    'order' => 0,
                    'enabled' => true,
                    'config' => [
                        'showIntro' => true,
                        'titleSmall' => $category->name,
                        'titleLarge' => 'Latest',
                        'bgColor' => 'white',
                    ],
                    'dataSource' => [
                        'action' => 'byCategory',
                        'params' => ['slug' => $category->slug],
                    ],
                    'slots' => array_map(fn ($i) => [
                        'index' => $i,
                        'mode' => 'dynamic',
                        'postId' => null,
                    ], range(0, 8)),
                ],
            ],
            'version' => 1,
            'updatedAt' => null,
            'updatedBy' => null,
        ];
    }

    /**
     * Get default layout configuration for a tag.
     *
     * @return array<string, mixed>
     */
    protected function getDefaultTagConfiguration(Tag $tag): array
    {
        return [
            'sections' => [
                [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'type' => 'latest-updates',
                    'order' => 0,
                    'enabled' => true,
                    'config' => [
                        'showIntro' => true,
                        'titleSmall' => $tag->name,
                        'titleLarge' => 'Tagged',
                        'bgColor' => 'white',
                    ],
                    'dataSource' => [
                        'action' => 'byTag',
                        'params' => ['slug' => $tag->slug],
                    ],
                    'slots' => array_map(fn ($i) => [
                        'index' => $i,
                        'mode' => 'dynamic',
                        'postId' => null,
                    ], range(0, 8)),
                ],
            ],
            'version' => 1,
            'updatedAt' => null,
            'updatedBy' => null,
        ];
    }
}
