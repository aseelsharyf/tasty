<?php

namespace App\Services\Layouts\Sections;

class CarouselSection extends AbstractSectionDefinition
{
    public function type(): string
    {
        return 'carousel';
    }

    public function name(): string
    {
        return 'Carousel';
    }

    public function description(): string
    {
        return 'Horizontal scroll carousel of posts without intro card.';
    }

    public function icon(): string
    {
        return 'i-lucide-gallery-horizontal';
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
        return 12;
    }

    public function configSchema(): array
    {
        return [
            'bgColor' => [
                'type' => 'color',
                'label' => 'Background Color',
                'default' => 'yellow',
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
            'paddingTop' => [
                'type' => 'select',
                'label' => 'Top Padding',
                'default' => 'medium',
                'options' => ['none', 'small', 'medium', 'large'],
            ],
            'paddingBottom' => [
                'type' => 'select',
                'label' => 'Bottom Padding',
                'default' => 'medium',
                'options' => ['none', 'small', 'medium', 'large'],
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
            'layout' => 'scroll',
            'areas' => [
                ['id' => 'cards', 'label' => '', 'width' => 'full', 'height' => 'full', 'scroll' => 'horizontal', 'children' => [
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
