<?php

namespace App\Actions\Posts\Concerns;

use App\Services\Layouts\SectionCategoryMappingService;
use Illuminate\Database\Eloquent\Builder;

trait FiltersBySectionCategories
{
    /**
     * Apply section category restrictions to a query.
     */
    protected function applySectionCategoryFilter(Builder $query, ?string $sectionType): Builder
    {
        if ($sectionType === null) {
            return $query;
        }

        $mappingService = app(SectionCategoryMappingService::class);
        $allowedCategoryIds = $mappingService->getAllowedCategoryIds($sectionType);

        if ($allowedCategoryIds === null) {
            return $query;
        }

        return $query->whereHas(
            'categories',
            fn (Builder $q) => $q->whereIn('categories.id', $allowedCategoryIds)
        );
    }
}
