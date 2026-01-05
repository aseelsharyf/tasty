<?php

namespace App\View\Components\Sections\Concerns;

use App\Models\Post;
use App\Services\Layouts\SectionCategoryMappingService;

trait HasSectionCategoryRestrictions
{
    /**
     * Get the section type for this component.
     */
    abstract protected function sectionType(): string;

    /**
     * Check if a post is allowed for this section based on category restrictions.
     */
    protected function isPostAllowed(Post $post): bool
    {
        $mappingService = app(SectionCategoryMappingService::class);
        $postCategoryIds = $post->categories->pluck('id')->toArray();

        return $mappingService->isPostAllowedByCategories(
            $this->sectionType(),
            $postCategoryIds
        );
    }

    /**
     * Get allowed category IDs for this section.
     *
     * @return array<int>|null
     */
    protected function getAllowedCategoryIds(): ?array
    {
        $mappingService = app(SectionCategoryMappingService::class);

        return $mappingService->getAllowedCategoryIds($this->sectionType());
    }

    /**
     * Filter a collection of posts to only those allowed for this section.
     *
     * @param  \Illuminate\Support\Collection<int, Post>  $posts
     * @return \Illuminate\Support\Collection<int, Post>
     */
    protected function filterAllowedPosts($posts)
    {
        $allowedCategoryIds = $this->getAllowedCategoryIds();

        if ($allowedCategoryIds === null) {
            return $posts;
        }

        return $posts->filter(function ($post) use ($allowedCategoryIds) {
            if (! $post instanceof Post) {
                return true;
            }

            $postCategoryIds = $post->categories->pluck('id')->toArray();

            return ! empty(array_intersect($postCategoryIds, $allowedCategoryIds));
        });
    }
}
