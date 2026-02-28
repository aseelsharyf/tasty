<?php

namespace App\Services\Layouts;

use App\Models\PageLayout;
use App\Models\Setting;
use App\Services\PublicCacheService;

class LayoutSlotService
{
    /**
     * Find all layout slots that reference a given post ID.
     *
     * @return array<int, array{layoutType: string, layoutName: string, sectionId: string, sectionType: string, sectionLabel: string, slotIndex: int}>
     */
    public function getSlotUsages(int $postId): array
    {
        $usages = [];

        // Check homepage layout
        $homepageConfig = Setting::get('layouts.homepage');
        if ($homepageConfig && isset($homepageConfig['sections'])) {
            $usages = array_merge(
                $usages,
                $this->scanSections($homepageConfig['sections'], $postId, 'homepage', 'Homepage')
            );
        }

        // Check category and tag page layouts
        $pageLayouts = PageLayout::with('layoutable')->get();
        foreach ($pageLayouts as $pageLayout) {
            $configuration = $pageLayout->configuration;
            $sections = $configuration['sections'] ?? [];

            $layoutType = match ($pageLayout->layoutable_type) {
                'App\\Models\\Category' => 'category',
                'App\\Models\\Tag' => 'tag',
                default => 'other',
            };

            $layoutName = $pageLayout->layoutable?->name ?? 'Unknown';

            $usages = array_merge(
                $usages,
                $this->scanSections($sections, $postId, $layoutType, $layoutName, $pageLayout->id)
            );
        }

        return $usages;
    }

    /**
     * Replace post references in layout slots.
     *
     * @param  array<int, array{layoutType: string, sectionId: string, slotIndex: int, newPostId: int, pageLayoutId?: int}>  $replacements
     */
    public function replacePostInSlots(int $oldPostId, array $replacements): void
    {
        // Group replacements by layout
        $homepageReplacements = [];
        $pageLayoutReplacements = [];

        foreach ($replacements as $replacement) {
            if ($replacement['layoutType'] === 'homepage') {
                $homepageReplacements[] = $replacement;
            } else {
                $pageLayoutId = $replacement['pageLayoutId'] ?? null;
                if ($pageLayoutId) {
                    $pageLayoutReplacements[$pageLayoutId][] = $replacement;
                }
            }
        }

        // Apply homepage replacements
        if (! empty($homepageReplacements)) {
            $this->applyHomepageReplacements($homepageReplacements);
        }

        // Apply page layout replacements
        foreach ($pageLayoutReplacements as $pageLayoutId => $layoutReplacements) {
            $this->applyPageLayoutReplacements($pageLayoutId, $layoutReplacements);
        }

        PublicCacheService::flushPostCaches();
    }

    /**
     * Scan sections for slots referencing a post.
     *
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<int, array{layoutType: string, layoutName: string, sectionId: string, sectionType: string, sectionLabel: string, slotIndex: int, pageLayoutId?: int}>
     */
    protected function scanSections(array $sections, int $postId, string $layoutType, string $layoutName, ?int $pageLayoutId = null): array
    {
        $usages = [];
        $registry = app(SectionRegistry::class);

        foreach ($sections as $section) {
            $slots = $section['slots'] ?? [];
            $sectionId = $section['id'] ?? '';
            $sectionType = $section['type'] ?? '';

            $definition = $registry->get($sectionType);
            $sectionLabel = $definition?->name() ?? ucfirst(str_replace('-', ' ', $sectionType));

            foreach ($slots as $slot) {
                if (($slot['mode'] ?? '') === 'manual' && ($slot['postId'] ?? null) === $postId) {
                    $usage = [
                        'layoutType' => $layoutType,
                        'layoutName' => $layoutName,
                        'sectionId' => $sectionId,
                        'sectionType' => $sectionType,
                        'sectionLabel' => $sectionLabel,
                        'slotIndex' => $slot['index'] ?? 0,
                    ];

                    if ($pageLayoutId) {
                        $usage['pageLayoutId'] = $pageLayoutId;
                    }

                    $usages[] = $usage;
                }
            }
        }

        return $usages;
    }

    /**
     * Apply replacements to the homepage layout configuration.
     *
     * @param  array<int, array{sectionId: string, slotIndex: int, newPostId: int}>  $replacements
     */
    protected function applyHomepageReplacements(array $replacements): void
    {
        $config = Setting::get('layouts.homepage');
        if (! $config || ! isset($config['sections'])) {
            return;
        }

        foreach ($replacements as $replacement) {
            foreach ($config['sections'] as &$section) {
                if ($section['id'] === $replacement['sectionId']) {
                    foreach ($section['slots'] as &$slot) {
                        if (($slot['index'] ?? 0) === $replacement['slotIndex']) {
                            $slot['postId'] = $replacement['newPostId'];
                            break;
                        }
                    }
                    unset($slot);
                    break;
                }
            }
            unset($section);
        }

        $config['version'] = ($config['version'] ?? 0) + 1;
        $config['updatedAt'] = now()->toIso8601String();

        Setting::set('layouts.homepage', $config, 'layouts');
    }

    /**
     * Apply replacements to a page layout configuration.
     *
     * @param  array<int, array{sectionId: string, slotIndex: int, newPostId: int}>  $replacements
     */
    protected function applyPageLayoutReplacements(int $pageLayoutId, array $replacements): void
    {
        $pageLayout = PageLayout::find($pageLayoutId);
        if (! $pageLayout) {
            return;
        }

        $configuration = $pageLayout->configuration;
        $sections = $configuration['sections'] ?? [];

        foreach ($replacements as $replacement) {
            foreach ($sections as &$section) {
                if ($section['id'] === $replacement['sectionId']) {
                    foreach ($section['slots'] as &$slot) {
                        if (($slot['index'] ?? 0) === $replacement['slotIndex']) {
                            $slot['postId'] = $replacement['newPostId'];
                            break;
                        }
                    }
                    unset($slot);
                    break;
                }
            }
            unset($section);
        }

        $configuration['sections'] = $sections;
        $pageLayout->update(['configuration' => $configuration]);
    }
}
