<?php

namespace App\Services\Layouts;

use App\Models\PageLayout;
use App\Models\Post;
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
     * Get all manual-mode slots across all layouts.
     *
     * @return array<int, array{layoutType: string, layoutName: string, sectionId: string, sectionType: string, sectionLabel: string, slotIndex: int, slotLabel: string, currentPostId: int|null, pageLayoutId: int|null}>
     */
    public function getManualSlots(): array
    {
        $slots = [];

        // Scan homepage layout
        $homepageConfig = Setting::get('layouts.homepage');
        if ($homepageConfig && isset($homepageConfig['sections'])) {
            $slots = array_merge(
                $slots,
                $this->scanManualSlots($homepageConfig['sections'], 'homepage', 'Homepage')
            );
        }

        // Scan category and tag page layouts
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

            $slots = array_merge(
                $slots,
                $this->scanManualSlots($sections, $layoutType, $layoutName, $pageLayout->id)
            );
        }

        // Eager-load current post titles and images
        $postIds = array_filter(array_column($slots, 'currentPostId'));
        $posts = [];
        if (! empty($postIds)) {
            $posts = Post::whereIn('id', $postIds)
                ->with('featuredMedia')
                ->get(['id', 'title', 'status', 'featured_media_id'])
                ->keyBy('id');
        }

        foreach ($slots as &$slot) {
            $post = $posts[$slot['currentPostId']] ?? null;
            $slot['currentPostTitle'] = $post?->title;
            $slot['currentPostStatus'] = $post?->status;
            $slot['currentPostImage'] = $post?->featured_image_thumb;
        }
        unset($slot);

        return $slots;
    }

    /**
     * Assign a post to a specific layout slot.
     */
    public function assignPostToSlot(
        int $postId,
        string $layoutType,
        string $sectionId,
        int $slotIndex,
        ?int $pageLayoutId = null
    ): void {
        $replacement = [
            'layoutType' => $layoutType,
            'sectionId' => $sectionId,
            'slotIndex' => $slotIndex,
            'newPostId' => $postId,
        ];

        if ($layoutType === 'homepage') {
            $this->applyHomepageReplacements([$replacement]);
        } elseif ($pageLayoutId) {
            $this->applyPageLayoutReplacements($pageLayoutId, [$replacement]);
        }

        PublicCacheService::flushPostCaches();
    }

    /**
     * Scan sections for manual-mode slots (regardless of post assignment).
     *
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<int, array{layoutType: string, layoutName: string, sectionId: string, sectionType: string, sectionLabel: string, slotIndex: int, slotLabel: string, currentPostId: int|null, pageLayoutId: int|null}>
     */
    protected function scanManualSlots(array $sections, string $layoutType, string $layoutName, ?int $pageLayoutId = null): array
    {
        $slots = [];
        $registry = app(SectionRegistry::class);

        foreach ($sections as $section) {
            $sectionSlots = $section['slots'] ?? [];
            $sectionId = $section['id'] ?? '';
            $sectionType = $section['type'] ?? '';

            $definition = $registry->get($sectionType);
            $sectionLabel = $definition?->name() ?? ucfirst(str_replace('-', ' ', $sectionType));
            $slotLabels = $definition?->slotLabels() ?? [];

            foreach ($sectionSlots as $slot) {
                if (($slot['mode'] ?? '') !== 'manual') {
                    continue;
                }

                $index = $slot['index'] ?? 0;

                $slots[] = [
                    'layoutType' => $layoutType,
                    'layoutName' => $layoutName,
                    'sectionId' => $sectionId,
                    'sectionType' => $sectionType,
                    'sectionLabel' => $sectionLabel,
                    'slotIndex' => $index,
                    'slotLabel' => $slotLabels[$index] ?? 'Slot '.($index + 1),
                    'currentPostId' => $slot['postId'] ?? null,
                    'pageLayoutId' => $pageLayoutId,
                ];
            }
        }

        return $slots;
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
