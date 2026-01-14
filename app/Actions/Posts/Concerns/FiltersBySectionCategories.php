<?php

namespace App\Actions\Posts\Concerns;

use App\Services\Layouts\SectionCategoryMappingService;
use Illuminate\Database\Eloquent\Builder;

trait FiltersBySectionCategories
{
    /**
     * Apply section category restrictions to a query.
     *
     * If the section HAS allowed categories: only show posts from those categories.
     * If the section has NO allowed categories: exclude posts from categories reserved by other sections.
     */
    protected function applySectionCategoryFilter(Builder $query, ?string $sectionType): Builder
    {
        if ($sectionType === null) {
            return $query;
        }

        $mappingService = app(SectionCategoryMappingService::class);
        $allowedCategoryIds = $mappingService->getAllowedCategoryIds($sectionType);

        if ($allowedCategoryIds !== null) {
            // Section has specific categories - only show posts from those categories
            return $query->whereHas(
                'categories',
                fn (Builder $q) => $q->whereIn('categories.id', $allowedCategoryIds)
            );
        }

        // Section has no restrictions - exclude posts from categories reserved by other sections
        $reservedCategoryIds = $mappingService->getCategoryIdsReservedByOtherSections($sectionType);

        if (empty($reservedCategoryIds)) {
            return $query;
        }

        // Exclude posts that have ANY of the reserved categories
        // These posts will appear in their designated sections instead
        return $query->whereDoesntHave(
            'categories',
            fn (Builder $q) => $q->whereIn('categories.id', $reservedCategoryIds)
        );
    }
}
