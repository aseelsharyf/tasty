<?php

namespace App\Services\Layouts\Sections;

class FeaturedLocationSection extends AbstractSectionDefinition
{
    public function type(): string
    {
        return 'featured-location';
    }

    public function name(): string
    {
        return 'Featured Location';
    }

    public function description(): string
    {
        return 'Location feature section with curved overlay and destination details.';
    }

    public function icon(): string
    {
        return 'i-lucide-map-pin';
    }

    public function slotCount(): int
    {
        return 6; // 1 featured + 5 carousel posts
    }

    public function minSlots(): int
    {
        return 1;
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
            'textColor' => [
                'type' => 'select',
                'label' => 'Text Color',
                'default' => 'blue-black',
                'options' => ['blue-black', 'white'],
            ],
            'buttonVariant' => [
                'type' => 'select',
                'label' => 'Button Style',
                'default' => 'white',
                'options' => ['white', 'yellow'],
            ],
            'buttonText' => [
                'type' => 'text',
                'label' => 'Button Text',
                'default' => 'Read More',
            ],
            'tag1' => [
                'type' => 'text',
                'label' => 'Primary Tag',
                'default' => 'TASTY FEATURE',
            ],
            'tag2' => [
                'type' => 'text',
                'label' => 'Secondary Tag',
                'default' => '',
                'placeholder' => 'Uses category if empty',
            ],
        ];
    }

    public function supportedActions(): array
    {
        return ['recent', 'trending', 'byTag', 'byCategory'];
    }

    public function slotLabels(): array
    {
        $labels = [0 => 'Featured Location'];
        for ($i = 1; $i < $this->maxSlots(); $i++) {
            $labels[$i] = 'Card '.$i;
        }

        return $labels;
    }

    public function slotSchema(): array
    {
        return [
            'kicker' => [
                'type' => 'text',
                'label' => 'Kicker (Large Text)',
                'placeholder' => 'Enter large header text (e.g. CEYLON)',
            ],
            'title' => [
                'type' => 'text',
                'label' => 'Title',
                'placeholder' => 'Enter title',
            ],
            'description' => [
                'type' => 'textarea',
                'label' => 'Description',
                'placeholder' => 'Enter description',
            ],
            'image' => [
                'type' => 'media',
                'label' => 'Location Image',
                'placeholder' => 'Select or enter image URL',
            ],
            'category' => [
                'type' => 'text',
                'label' => 'Category',
                'placeholder' => 'Enter category',
            ],
            'url' => [
                'type' => 'text',
                'label' => 'Link URL',
                'placeholder' => 'Enter link URL',
            ],
        ];
    }

    public function previewSchema(): array
    {
        return [
            'layout' => 'featured-carousel',
            'areas' => [
                ['id' => 'slot-0', 'label' => 'Featured', 'width' => 'full', 'height' => '2/3', 'slotIndex' => 0],
                ['id' => 'carousel', 'label' => 'Carousel', 'width' => 'full', 'height' => '1/3', 'scroll' => 'horizontal', 'children' => [
                    ['id' => 'slot-1', 'label' => '1', 'slotIndex' => 1],
                    ['id' => 'slot-2', 'label' => '2', 'slotIndex' => 2],
                    ['id' => 'slot-3', 'label' => '3', 'slotIndex' => 3],
                    ['id' => 'slot-4', 'label' => '4', 'slotIndex' => 4],
                    ['id' => 'slot-5', 'label' => '5', 'slotIndex' => 5],
                ]],
            ],
        ];
    }
}
