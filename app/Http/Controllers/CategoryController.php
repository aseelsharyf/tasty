<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Services\Layouts\SectionDataResolver;
use App\Services\PublicCacheService;
use App\Services\SeoService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function __construct(
        protected SectionDataResolver $dataResolver,
        protected SeoService $seoService,
    ) {}

    /**
     * Display posts for a specific category.
     */
    public function show(Category $category): Response
    {
        $page = request()->integer('page', 1);
        $cacheKey = "public:category:{$category->slug}:page:{$page}";

        $html = Cache::remember($cacheKey, PublicCacheService::listingTtl(), function () use ($category) {
            // Set SEO
            $this->seoService->setCategory($category);

            // Check for custom layout and load featured image
            $category->load(['pageLayout', 'featuredImage']);

            if ($category->hasCustomLayout()) {
                return $this->renderCustomLayout($category);
            }

            // Fallback to default view — include posts from sub-categories
            $categoryIds = collect([$category->id])->merge($category->descendantIds());

            $posts = Post::query()
                ->published()
                ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds))
                ->with(['author', 'categories', 'tags', 'featuredMedia'])
                ->latest('published_at')
                ->paginate(12);

            return view('categories.show', [
                'category' => $category,
                'posts' => $posts,
            ])->render();
        });

        return new Response($html);
    }

    /**
     * Render category page with custom layout.
     */
    protected function renderCustomLayout(Category $category): string
    {
        $configuration = $category->getLayoutConfiguration();

        // Set category context so sections scope posts to this category
        $this->dataResolver->setCategory($category);

        // Get enabled sections sorted by order
        $sections = collect($configuration['sections'] ?? [])
            ->filter(fn (array $section) => $section['enabled'] ?? true)
            ->sortBy('order')
            ->map(fn (array $section) => [
                'type' => $section['type'],
                'data' => $this->dataResolver->resolve($section),
            ])
            ->values()
            ->all();

        return view('pages.dynamic-layout', [
            'title' => $category->name,
            'subtitle' => $category->description,
            'sections' => $sections,
            'entity' => $category,
            'entityType' => 'category',
        ])->render();
    }
}
