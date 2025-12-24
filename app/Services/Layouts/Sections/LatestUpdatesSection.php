<?php

namespace App\Services\Layouts\Sections;

class LatestUpdatesSection extends AbstractSectionDefinition
{
    public function type(): string
    {
        return 'latest-updates';
    }

    public function name(): string
    {
        return 'Latest Updates';
    }

    public function description(): string
    {
        return 'Featured post with a grid of recent posts and optional load more functionality.';
    }

    public function icon(): string
    {
        return 'i-lucide-newspaper';
    }

    public function slotCount(): int
    {
        return 5; // 1 featured + 4 grid posts
    }

    public function minSlots(): int
    {
        return 2; // 1 featured + at least 1 grid post
    }

    public function maxSlots(): int
    {
        return 20; // 1 featured + up to 19 grid posts
    }

    public function configSchema(): array
    {
        return [
            'introImage' => [
                'type' => 'media',
                'label' => 'Intro Image',
                'default' => '',
            ],
            'introImageAlt' => [
                'type' => 'text',
                'label' => 'Intro Image Alt Text',
                'default' => '',
            ],
            'titleSmall' => [
                'type' => 'text',
                'label' => 'Small Title',
                'default' => 'Latest',
            ],
            'titleLarge' => [
                'type' => 'text',
                'label' => 'Large Title',
                'default' => 'Updates',
            ],
            'description' => [
                'type' => 'textarea',
                'label' => 'Description',
                'default' => '',
            ],
            'buttonText' => [
                'type' => 'text',
                'label' => 'Load More Button Text',
                'default' => 'More Updates',
            ],
            'showLoadMore' => [
                'type' => 'toggle',
                'label' => 'Show Load More Button',
                'default' => true,
            ],
            'featuredCount' => [
                'type' => 'number',
                'label' => 'Featured Posts Count',
                'default' => 1,
            ],
            'postsCount' => [
                'type' => 'number',
                'label' => 'Grid Posts Count',
                'default' => 4,
            ],
        ];
    }

    public function supportedActions(): array
    {
        return ['recent', 'trending', 'byTag', 'byCategory'];
    }

    public function getSlotLabel(int $index): string
    {
        if ($index === 0) {
            return 'Featured Post';
        }

        return 'Grid Post '.$index;
    }

    public function slotLabels(): array
    {
        $labels = [];
        for ($i = 0; $i < $this->maxSlots(); $i++) {
            $labels[$i] = $this->getSlotLabel($i);
        }

        return $labels;
    }

    public function previewSchema(): array
    {
        return [
            'layout' => 'featured-grid',
            'areas' => [
                ['id' => 'intro', 'label' => 'Intro', 'width' => '1/4', 'height' => 'full'],
                ['id' => 'featured', 'label' => 'Featured', 'width' => '1/4', 'height' => 'full', 'slotIndex' => 0],
                ['id' => 'grid', 'label' => '', 'width' => '1/2', 'height' => 'full', 'gridCols' => 2, 'children' => [
                    ['id' => 'slot-1', 'label' => '1', 'slotIndex' => 1],
                    ['id' => 'slot-2', 'label' => '2', 'slotIndex' => 2],
                    ['id' => 'slot-3', 'label' => '3', 'slotIndex' => 3],
                    ['id' => 'slot-4', 'label' => '4', 'slotIndex' => 4],
                ]],
            ],
        ];
    }
}
