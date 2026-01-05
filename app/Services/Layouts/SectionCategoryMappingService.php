<?php

namespace App\Services\Layouts;

use App\Models\Category;
use App\Models\SectionCategoryMapping;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SectionCategoryMappingService
{
    protected const CACHE_KEY = 'section_category_mappings';

    protected const CACHE_TTL = 3600;

    /**
     * Get allowed category IDs for a section type.
     * Returns null if no restrictions (all categories allowed).
     *
     * @return array<int>|null
     */
    public function getAllowedCategoryIds(string $sectionType): ?array
    {
        $mappings = $this->getAllMappings();

        if (! isset($mappings[$sectionType]) || empty($mappings[$sectionType])) {
            return null;
        }

        return $mappings[$sectionType];
    }

    /**
     * Get allowed categories for a section type.
     *
     * @return Collection<int, Category>|null
     */
    public function getAllowedCategories(string $sectionType): ?Collection
    {
        $categoryIds = $this->getAllowedCategoryIds($sectionType);

        if ($categoryIds === null) {
            return null;
        }

        return Category::whereIn('id', $categoryIds)->get();
    }

    /**
     * Check if a category is allowed for a section type.
     */
    public function isCategoryAllowed(string $sectionType, int $categoryId): bool
    {
        $allowedIds = $this->getAllowedCategoryIds($sectionType);

        if ($allowedIds === null) {
            return true;
        }

        return in_array($categoryId, $allowedIds);
    }

    /**
     * Check if a post (by its categories) is allowed for a section type.
     *
     * @param  array<int>  $postCategoryIds
     */
    public function isPostAllowedByCategories(string $sectionType, array $postCategoryIds): bool
    {
        $allowedIds = $this->getAllowedCategoryIds($sectionType);

        if ($allowedIds === null) {
            return true;
        }

        return ! empty(array_intersect($postCategoryIds, $allowedIds));
    }

    /**
     * Set allowed categories for a section type.
     *
     * @param  array<int>  $categoryIds
     */
    public function setAllowedCategories(string $sectionType, array $categoryIds): void
    {
        SectionCategoryMapping::where('section_type', $sectionType)->delete();

        foreach ($categoryIds as $categoryId) {
            SectionCategoryMapping::create([
                'section_type' => $sectionType,
                'category_id' => $categoryId,
            ]);
        }

        $this->clearCache();
    }

    /**
     * Get all mappings grouped by section type.
     *
     * @return array<string, array<int>>
     */
    public function getAllMappings(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return SectionCategoryMapping::query()
                ->get()
                ->groupBy('section_type')
                ->map(fn ($mappings) => $mappings->pluck('category_id')->toArray())
                ->toArray();
        });
    }

    /**
     * Get all mappings with full category data for admin UI.
     *
     * @return array<string, array<int, array{id: int, name: string, slug: string}>>
     */
    public function getAllMappingsWithCategories(): array
    {
        $mappings = SectionCategoryMapping::with('category')->get();

        $result = [];
        foreach ($mappings as $mapping) {
            if (! isset($result[$mapping->section_type])) {
                $result[$mapping->section_type] = [];
            }

            if ($mapping->category) {
                $result[$mapping->section_type][] = [
                    'id' => $mapping->category->id,
                    'name' => $mapping->category->name,
                    'slug' => $mapping->category->slug,
                ];
            }
        }

        return $result;
    }

    /**
     * Clear the cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
