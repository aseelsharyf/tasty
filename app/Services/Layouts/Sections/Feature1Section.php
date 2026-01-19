<?php

namespace App\Services\Layouts\Sections;

class Feature1Section extends AbstractSectionDefinition
{
    public function type(): string
    {
        return 'feature-1';
    }

    public function name(): string
    {
        return 'Feature Curve';
    }

    public function description(): string
    {
        return 'Full-width feature section with header and single post highlight.';
    }

    public function icon(): string
    {
        return 'i-lucide-star';
    }

    public function slotCount(): int
    {
        return 1;
    }

    public function maxSlots(): int
    {
        return 1;
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
                'options' => ['white', 'yellow', 'blue-black'],
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
        return []; // Only supports static and manual modes
    }

    public function slotLabels(): array
    {
        return [0 => 'Featured Post'];
    }

    public function slotSchema(): array
    {
        return [
            'kicker' => [
                'type' => 'text',
                'label' => 'Kicker (Large Text)',
                'placeholder' => 'Enter large header text',
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
                'label' => 'Featured Image',
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
                ['id' => 'slot-0', 'label' => 'Featured Post', 'width' => 'full', 'height' => 'full', 'slotIndex' => 0],
            ],
        ];
    }
}
