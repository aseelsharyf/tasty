<?php

namespace App\Services\Layouts;

use App\Services\Layouts\Sections\AbstractSectionDefinition;
use App\Services\Layouts\Sections\AddToCartSection;
use App\Services\Layouts\Sections\Feature1Section;
use App\Services\Layouts\Sections\Feature2Section;
use App\Services\Layouts\Sections\FeaturedLocationSection;
use App\Services\Layouts\Sections\FeaturedPersonSection;
use App\Services\Layouts\Sections\FeaturedVideoSection;
use App\Services\Layouts\Sections\HeroSection;
use App\Services\Layouts\Sections\LatestUpdatesSection;
use App\Services\Layouts\Sections\NewsletterSection;
use App\Services\Layouts\Sections\RecipeSection;
use App\Services\Layouts\Sections\ReviewSection;
use App\Services\Layouts\Sections\SpreadSection;

class SectionRegistry
{
    /**
     * @var array<string, AbstractSectionDefinition>
     */
    protected array $sections = [];

    public function __construct(protected SectionCategoryMappingService $mappingService)
    {
        $this->registerDefaults();
    }

    /**
     * Register the default section types.
     */
    protected function registerDefaults(): void
    {
        $this->register(new HeroSection);
        $this->register(new LatestUpdatesSection);
        $this->register(new SpreadSection);
        $this->register(new ReviewSection);
        $this->register(new RecipeSection);
        $this->register(new FeaturedPersonSection);
        $this->register(new FeaturedVideoSection);
        $this->register(new FeaturedLocationSection);
        $this->register(new Feature1Section);
        $this->register(new Feature2Section);
        $this->register(new NewsletterSection);
        $this->register(new AddToCartSection);
    }

    /**
     * Register a section type.
     */
    public function register(AbstractSectionDefinition $section): void
    {
        $this->sections[$section->type()] = $section;
    }

    /**
     * Get a section definition by type.
     */
    public function get(string $type): ?AbstractSectionDefinition
    {
        return $this->sections[$type] ?? null;
    }

    /**
     * Check if a section type exists.
     */
    public function has(string $type): bool
    {
        return isset($this->sections[$type]);
    }

    /**
     * Get all registered section types.
     *
     * @return array<string, AbstractSectionDefinition>
     */
    public function all(): array
    {
        return $this->sections;
    }

    /**
     * Get all section types as arrays (for API/frontend).
     *
     * @return array<string, array<string, mixed>>
     */
    public function toArray(): array
    {
        $result = [];
        $mappings = $this->mappingService->getAllMappingsWithCategories();

        foreach ($this->sections as $type => $section) {
            $sectionArray = $section->toArray();
            $sectionArray['allowedCategories'] = $mappings[$type] ?? null;
            $result[$type] = $sectionArray;
        }

        return $result;
    }

    /**
     * Get allowed category IDs for a section type.
     *
     * @return array<int>|null
     */
    public function getAllowedCategoryIds(string $type): ?array
    {
        return $this->mappingService->getAllowedCategoryIds($type);
    }

    /**
     * Check if a category is allowed for a section type.
     */
    public function isCategoryAllowed(string $type, int $categoryId): bool
    {
        return $this->mappingService->isCategoryAllowed($type, $categoryId);
    }

    /**
     * Get the mapping service.
     */
    public function getMappingService(): SectionCategoryMappingService
    {
        return $this->mappingService;
    }

    /**
     * Get the default configuration for a section type.
     *
     * @return array<string, mixed>
     */
    public function getDefaultConfig(string $type): array
    {
        $section = $this->get($type);

        return $section?->defaultConfig() ?? [];
    }

    /**
     * Get the default slots for a section type.
     *
     * @return array<int, array{index: int, mode: string, postId: int|null}>
     */
    public function getDefaultSlots(string $type): array
    {
        $section = $this->get($type);

        return $section?->defaultSlots() ?? [];
    }

    /**
     * Get the default data source for a section type.
     *
     * @return array{action: string, params: array<string, mixed>}
     */
    public function getDefaultDataSource(string $type): array
    {
        $section = $this->get($type);

        return $section?->defaultDataSource() ?? ['action' => 'recent', 'params' => []];
    }

    /**
     * Get available actions for a section type.
     *
     * @return array<string>
     */
    public function getSupportedActions(string $type): array
    {
        $section = $this->get($type);

        return $section?->supportedActions() ?? [];
    }

    /**
     * Validate section configuration.
     *
     * @param  array<string, mixed>  $config
     */
    public function validateConfig(string $type, array $config): bool
    {
        $section = $this->get($type);

        if (! $section) {
            return false;
        }

        $schema = $section->configSchema();

        foreach ($schema as $key => $field) {
            if (! array_key_exists($key, $config)) {
                continue; // Missing keys will use defaults
            }

            $value = $config[$key];

            // Basic type validation
            switch ($field['type']) {
                case 'select':
                    if (isset($field['options']) && ! in_array($value, $field['options'])) {
                        return false;
                    }
                    break;

                case 'toggle':
                    if (! is_bool($value)) {
                        return false;
                    }
                    break;

                case 'number':
                    if (! is_numeric($value)) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }
}
