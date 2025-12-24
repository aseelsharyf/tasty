<?php

namespace App\Services\Layouts;

use App\Models\Setting;
use Illuminate\Support\Str;

class HomepageConfigurationService
{
    protected SectionRegistry $registry;

    public function __construct(SectionRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Get the homepage configuration.
     *
     * @return array{sections: array<int, array<string, mixed>>, version: int, updatedAt: string|null, updatedBy: int|null}
     */
    public function getConfiguration(): array
    {
        $config = Setting::get('layouts.homepage');

        if (! $config || ! isset($config['sections'])) {
            return $this->getDefaultConfiguration();
        }

        return $config;
    }

    /**
     * Save the homepage configuration.
     *
     * @param  array{sections: array<int, array<string, mixed>>}  $config
     */
    public function saveConfiguration(array $config, int $userId): void
    {
        $config['version'] = ($this->getConfiguration()['version'] ?? 0) + 1;
        $config['updatedAt'] = now()->toIso8601String();
        $config['updatedBy'] = $userId;

        Setting::set('layouts.homepage', $config, 'layouts');
    }

    /**
     * Get the default homepage configuration based on current home.blade.php.
     *
     * @return array{sections: array<int, array<string, mixed>>, version: int, updatedAt: string|null, updatedBy: int|null}
     */
    public function getDefaultConfiguration(): array
    {
        return [
            'sections' => [
                $this->createSection('hero', 0, [
                    'alignment' => 'center',
                    'bgColor' => 'yellow',
                    'buttonText' => 'Read More',
                    'buttonColor' => 'white',
                ]),
                $this->createSection('latest-updates', 1, [
                    'introImage' => '',
                    'introImageAlt' => 'Latest Updates',
                    'titleSmall' => 'Latest',
                    'titleLarge' => 'Updates',
                    'description' => 'The flavors, characters, and tiny island obsessions that makes the Maldivian food culture.',
                    'buttonText' => 'More Updates',
                    'showLoadMore' => true,
                    'featuredCount' => 1,
                    'postsCount' => 4,
                ]),
                $this->createSection('featured-person', 2, [
                    'bgColor' => 'yellow',
                    'buttonText' => 'Read More',
                    'tag1' => 'TASTY FEATURE',
                    'tag2' => '',
                ]),
                $this->createSection('spread', 3, [
                    'showIntro' => true,
                    'introImage' => '',
                    'introImageAlt' => 'The Spread',
                    'titleSmall' => 'The',
                    'titleLarge' => 'SPREAD',
                    'description' => 'Explore the latest from our kitchen to yours.',
                    'bgColor' => 'yellow',
                    'mobileLayout' => 'scroll',
                    'showDividers' => true,
                    'dividerColor' => 'white',
                    'count' => 4,
                ]),
                $this->createSection('featured-video', 4, [
                    'buttonText' => 'Watch',
                    'overlayColor' => '#FFE762',
                    'showSectionGradient' => true,
                    'sectionGradientDirection' => 'top',
                    'sectionBgColor' => '',
                ]),
                $this->createSection('review', 5, [
                    'showIntro' => true,
                    'introImage' => '',
                    'introImageAlt' => 'On the Menu',
                    'titleSmall' => 'On the',
                    'titleLarge' => 'Menu',
                    'description' => 'Restaurant reviews, chef crushes, and the dishes we can\'t stop talking about.',
                    'mobileLayout' => 'scroll',
                    'showDividers' => true,
                    'dividerColor' => 'white',
                    'buttonText' => 'More Reviews',
                    'showLoadMore' => true,
                    'count' => 5,
                ]),
                $this->createSection('featured-location', 6, [
                    'bgColor' => 'yellow',
                    'textColor' => 'blue-black',
                    'buttonVariant' => 'white',
                    'buttonText' => 'Read More',
                    'tag1' => 'TASTY FEATURE',
                    'tag2' => '',
                ]),
                $this->createSection('spread', 7, [
                    'showIntro' => false,
                    'introImage' => '',
                    'introImageAlt' => '',
                    'titleSmall' => '',
                    'titleLarge' => '',
                    'description' => '',
                    'bgColor' => 'yellow',
                    'mobileLayout' => 'scroll',
                    'showDividers' => true,
                    'dividerColor' => 'white',
                    'count' => 5,
                ]),
                $this->createSection('recipe', 8, [
                    'showIntro' => true,
                    'introImage' => '',
                    'introImageAlt' => 'Everyday Cooking',
                    'titleSmall' => 'Everyday',
                    'titleLarge' => 'COOKING',
                    'description' => 'The flavors, characters, and tiny island obsessions that makes the Maldivian food culture.',
                    'bgColor' => 'yellow',
                    'gradient' => 'top',
                    'mobileLayout' => 'grid',
                    'showDividers' => false,
                    'dividerColor' => 'white',
                    'count' => 3,
                ]),
                $this->createSection('add-to-cart', 9, [
                    'title' => 'ADD TO CART',
                    'description' => 'Ingredients, tools, and staples we actually use.',
                    'bgColor' => 'white',
                ]),
                $this->createSection('newsletter', 10, [
                    'title' => 'COME HUNGRY, LEAVE INSPIRED. SIGN UP FOR TASTY UPDATES.',
                    'placeholder' => 'Enter your Email',
                    'buttonText' => 'SUBSCRIBE',
                    'bgColor' => '#F3F4F6',
                ]),
            ],
            'version' => 1,
            'updatedAt' => null,
            'updatedBy' => null,
        ];
    }

    /**
     * Create a section configuration array.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function createSection(string $type, int $order, array $config = []): array
    {
        $definition = $this->registry->get($type);

        $defaultConfig = $definition?->defaultConfig() ?? [];
        $mergedConfig = array_merge($defaultConfig, $config);

        return [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'order' => $order,
            'enabled' => true,
            'config' => $mergedConfig,
            'dataSource' => $definition?->defaultDataSource() ?? ['action' => 'recent', 'params' => []],
            'slots' => $definition?->defaultSlots() ?? [],
        ];
    }

    /**
     * Add a new section to the configuration.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function addSection(string $type, ?int $position = null, array $config = []): array
    {
        $currentConfig = $this->getConfiguration();
        $sections = $currentConfig['sections'];

        // Determine order
        $order = $position ?? count($sections);

        // Create new section
        $newSection = $this->createSection($type, $order, $config);

        // Insert at position and reorder
        if ($position !== null && $position < count($sections)) {
            // Shift orders for sections after the insertion point
            foreach ($sections as &$section) {
                if ($section['order'] >= $order) {
                    $section['order']++;
                }
            }
            unset($section);

            // Insert at position
            array_splice($sections, $position, 0, [$newSection]);
        } else {
            $sections[] = $newSection;
        }

        return $newSection;
    }

    /**
     * Remove a section from the configuration.
     *
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<int, array<string, mixed>>
     */
    public function removeSection(array $sections, string $sectionId): array
    {
        $filtered = array_filter($sections, fn ($s) => $s['id'] !== $sectionId);

        // Reorder remaining sections
        $reordered = array_values($filtered);
        foreach ($reordered as $index => &$section) {
            $section['order'] = $index;
        }

        return $reordered;
    }

    /**
     * Reorder sections.
     *
     * @param  array<int, array<string, mixed>>  $sections
     * @param  array<int, string>  $newOrder  Array of section IDs in new order
     * @return array<int, array<string, mixed>>
     */
    public function reorderSections(array $sections, array $newOrder): array
    {
        $sectionMap = [];
        foreach ($sections as $section) {
            $sectionMap[$section['id']] = $section;
        }

        $reordered = [];
        foreach ($newOrder as $index => $id) {
            if (isset($sectionMap[$id])) {
                $section = $sectionMap[$id];
                $section['order'] = $index;
                $reordered[] = $section;
            }
        }

        return $reordered;
    }
}
