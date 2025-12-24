<?php

namespace App\Services\Layouts\Sections;

class ReviewSection extends AbstractSectionDefinition
{
    public function type(): string
    {
        return 'review';
    }

    public function name(): string
    {
        return 'Reviews';
    }

    public function description(): string
    {
        return 'Review cards section with optional intro and load more functionality.';
    }

    public function icon(): string
    {
        return 'i-lucide-star';
    }

    public function slotCount(): int
    {
        return 5;
    }

    public function minSlots(): int
    {
        return 2;
    }

    public function maxSlots(): int
    {
        return 15;
    }

    public function configSchema(): array
    {
        return [
            'showIntro' => [
                'type' => 'toggle',
                'label' => 'Show Intro Card',
                'default' => true,
            ],
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
                'default' => 'On the',
            ],
            'titleLarge' => [
                'type' => 'text',
                'label' => 'Large Title',
                'default' => 'MENU',
            ],
            'description' => [
                'type' => 'textarea',
                'label' => 'Description',
                'default' => '',
            ],
            'mobileLayout' => [
                'type' => 'select',
                'label' => 'Mobile Layout',
                'default' => 'scroll',
                'options' => ['scroll', 'grid'],
            ],
            'showDividers' => [
                'type' => 'toggle',
                'label' => 'Show Dividers',
                'default' => true,
            ],
            'dividerColor' => [
                'type' => 'select',
                'label' => 'Divider Color',
                'default' => 'white',
                'options' => ['white', 'gray'],
            ],
            'buttonText' => [
                'type' => 'text',
                'label' => 'Load More Button Text',
                'default' => 'More Reviews',
            ],
            'showLoadMore' => [
                'type' => 'toggle',
                'label' => 'Show Load More Button',
                'default' => true,
            ],
            'count' => [
                'type' => 'number',
                'label' => 'Number of Posts',
                'default' => 5,
            ],
        ];
    }

    public function supportedActions(): array
    {
        return ['recent', 'trending', 'byTag', 'byCategory'];
    }

    public function slotLabels(): array
    {
        $labels = [];
        for ($i = 0; $i < $this->maxSlots(); $i++) {
            $labels[$i] = 'Review '.($i + 1);
        }

        return $labels;
    }

    public function previewSchema(): array
    {
        return [
            'layout' => 'intro-scroll',
            'areas' => [
                ['id' => 'intro', 'label' => 'Intro', 'width' => '1/4', 'height' => 'full'],
                ['id' => 'reviews', 'label' => '', 'width' => '3/4', 'height' => 'full', 'scroll' => 'horizontal', 'children' => [
                    ['id' => 'slot-0', 'label' => '1', 'slotIndex' => 0],
                    ['id' => 'slot-1', 'label' => '2', 'slotIndex' => 1],
                    ['id' => 'slot-2', 'label' => '3', 'slotIndex' => 2],
                    ['id' => 'slot-3', 'label' => '4', 'slotIndex' => 3],
                    ['id' => 'slot-4', 'label' => '5', 'slotIndex' => 4],
                ]],
            ],
        ];
    }
}
