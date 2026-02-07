<?php

namespace App\Services\Layouts\Sections;

class FeaturedPersonSection extends AbstractSectionDefinition
{
    public function type(): string
    {
        return 'featured-person';
    }

    public function name(): string
    {
        return 'Featured Person';
    }

    public function description(): string
    {
        return 'Full-width feature section highlighting a person or profile.';
    }

    public function icon(): string
    {
        return 'i-lucide-user';
    }

    public function slotCount(): int
    {
        return 1;
    }

    public function maxSlots(): int
    {
        return 1; // Featured person always has exactly 1 slot
    }

    public function configSchema(): array
    {
        return [
            'bgColor' => [
                'type' => 'color',
                'label' => 'Background Color',
                'default' => 'yellow',
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
        return [0 => 'Featured Person'];
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
                'label' => 'Photo',
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
                ['id' => 'slot-0', 'label' => 'Featured Person', 'width' => 'full', 'height' => 'full', 'slotIndex' => 0],
            ],
        ];
    }
}
