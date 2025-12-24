<?php

namespace App\Services\Layouts\Sections;

class SpreadSection extends AbstractSectionDefinition
{
    public function type(): string
    {
        return 'spread';
    }

    public function name(): string
    {
        return 'The Spread';
    }

    public function description(): string
    {
        return 'Horizontal scroll carousel of posts with optional intro card.';
    }

    public function icon(): string
    {
        return 'i-lucide-columns-3';
    }

    public function slotCount(): int
    {
        return 4;
    }

    public function minSlots(): int
    {
        return 2;
    }

    public function maxSlots(): int
    {
        return 12;
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
                'default' => 'The',
            ],
            'titleLarge' => [
                'type' => 'text',
                'label' => 'Large Title',
                'default' => 'SPREAD',
            ],
            'description' => [
                'type' => 'textarea',
                'label' => 'Description',
                'default' => '',
            ],
            'bgColor' => [
                'type' => 'color',
                'label' => 'Background Color',
                'default' => 'yellow',
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
            'count' => [
                'type' => 'number',
                'label' => 'Number of Posts',
                'default' => 4,
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
            $labels[$i] = 'Card '.($i + 1);
        }

        return $labels;
    }

    public function previewSchema(): array
    {
        return [
            'layout' => 'intro-scroll',
            'areas' => [
                ['id' => 'intro', 'label' => 'Intro', 'width' => '1/4', 'height' => 'full'],
                ['id' => 'cards', 'label' => '', 'width' => '3/4', 'height' => 'full', 'scroll' => 'horizontal', 'children' => [
                    ['id' => 'slot-0', 'label' => '1', 'slotIndex' => 0],
                    ['id' => 'slot-1', 'label' => '2', 'slotIndex' => 1],
                    ['id' => 'slot-2', 'label' => '3', 'slotIndex' => 2],
                    ['id' => 'slot-3', 'label' => '4', 'slotIndex' => 3],
                ]],
            ],
        ];
    }
}
