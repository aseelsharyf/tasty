<?php

namespace App\Services\Layouts\Sections;

class FeaturedVideoSection extends AbstractSectionDefinition
{
    public function type(): string
    {
        return 'featured-video';
    }

    public function name(): string
    {
        return 'Featured Video';
    }

    public function description(): string
    {
        return 'Video feature section with play button overlay and post details.';
    }

    public function icon(): string
    {
        return 'i-lucide-play-circle';
    }

    public function slotCount(): int
    {
        return 1;
    }

    public function maxSlots(): int
    {
        return 1; // Featured video always has exactly 1 slot
    }

    public function configSchema(): array
    {
        return [
            'buttonText' => [
                'type' => 'text',
                'label' => 'Button Text',
                'default' => 'Watch',
            ],
            'overlayColor' => [
                'type' => 'color',
                'label' => 'Overlay Color',
                'default' => '#FFE762',
            ],
            'showSectionGradient' => [
                'type' => 'toggle',
                'label' => 'Show Section Gradient',
                'default' => true,
            ],
            'sectionGradientDirection' => [
                'type' => 'select',
                'label' => 'Gradient Direction',
                'default' => 'top',
                'options' => ['top', 'bottom'],
            ],
            'sectionBgColor' => [
                'type' => 'color',
                'label' => 'Section Background Color',
                'default' => '',
                'placeholder' => 'Overrides gradient if set',
            ],
        ];
    }

    public function supportedActions(): array
    {
        return ['recent', 'trending', 'byTag', 'byCategory'];
    }

    public function slotLabels(): array
    {
        return [0 => 'Featured Video'];
    }

    public function slotSchema(): array
    {
        return [
            'title' => [
                'type' => 'text',
                'label' => 'Video Title',
                'placeholder' => 'Enter video title',
            ],
            'description' => [
                'type' => 'textarea',
                'label' => 'Description',
                'placeholder' => 'Enter description',
            ],
            'image' => [
                'type' => 'media',
                'label' => 'Thumbnail Image',
                'placeholder' => 'Select or enter thumbnail URL',
            ],
            'videoUrl' => [
                'type' => 'text',
                'label' => 'Video URL',
                'placeholder' => 'Enter YouTube/Vimeo URL',
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
                ['id' => 'slot-0', 'label' => 'Featured Video', 'width' => 'full', 'height' => 'full', 'slotIndex' => 0, 'showPlay' => true],
            ],
        ];
    }
}
