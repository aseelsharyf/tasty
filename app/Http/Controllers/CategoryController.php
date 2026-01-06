<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\Layouts\SectionDataResolver;
use Illuminate\Contracts\View\View;

class CategoryController extends Controller
{
    public function __construct(
        protected SectionDataResolver $dataResolver
    ) {}

    /**
     * Display posts for a specific category.
     */
    public function show(Category $category): View
    {
        // Check for custom layout
        $category->load('pageLayout');

        if ($category->hasCustomLayout()) {
            return $this->renderCustomLayout($category);
        }

        // Fallback to default view
        $posts = $category->posts()
            ->published()
            ->with(['author', 'categories', 'tags', 'featuredMedia'])
            ->latest('published_at')
            ->paginate(12);

        return view('categories.show', [
            'category' => $category,
            'posts' => $posts,
        ]);
    }

    /**
     * Render category page with custom layout.
     */
    protected function renderCustomLayout(Category $category): View
    {
        $configuration = $category->getLayoutConfiguration();

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
        ]);
    }
}
