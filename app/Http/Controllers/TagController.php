<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Services\Layouts\SectionDataResolver;
use Illuminate\Contracts\View\View;

class TagController extends Controller
{
    public function __construct(
        protected SectionDataResolver $dataResolver
    ) {}

    /**
     * Display posts for a specific tag.
     */
    public function show(Tag $tag): View
    {
        // Check for custom layout
        $tag->load('pageLayout');

        if ($tag->hasCustomLayout()) {
            return $this->renderCustomLayout($tag);
        }

        // Fallback to default view
        $posts = $tag->posts()
            ->published()
            ->with(['author', 'categories', 'tags', 'featuredMedia'])
            ->latest('published_at')
            ->paginate(12);

        return view('tags.show', [
            'tag' => $tag,
            'posts' => $posts,
        ]);
    }

    /**
     * Render tag page with custom layout.
     */
    protected function renderCustomLayout(Tag $tag): View
    {
        $configuration = $tag->getLayoutConfiguration();

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
            'title' => $tag->name,
            'subtitle' => null,
            'sections' => $sections,
            'entity' => $tag,
            'entityType' => 'tag',
        ]);
    }
}
