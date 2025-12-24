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
        return 1;
    }

    public function maxSlots(): int
    {
        return 1; // Featured location always has exactly 1 slot
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
        return [0 => 'Featured Location'];
    }

    public function slotSchema(): array
    {
        return [
            'title' => [
                'type' => 'text',
                'label' => 'Location Name',
                'placeholder' => 'Enter location name',
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
            'layout' => 'single',
            'areas' => [
                ['id' => 'slot-0', 'label' => 'Featured Location', 'width' => 'full', 'height' => 'full', 'slotIndex' => 0],
            ],
        ];
    }
}
