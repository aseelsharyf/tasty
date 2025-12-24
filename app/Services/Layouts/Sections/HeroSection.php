<?php

namespace App\Services\Layouts\Sections;

class HeroSection extends AbstractSectionDefinition
{
    public function type(): string
    {
        return 'hero';
    }

    public function name(): string
    {
        return 'Hero';
    }

    public function description(): string
    {
        return 'Full-width hero section with a featured post, large image background, and call-to-action.';
    }

    public function icon(): string
    {
        return 'i-lucide-layout-template';
    }

    public function slotCount(): int
    {
        return 1;
    }

    public function maxSlots(): int
    {
        return 1; // Hero always has exactly 1 slot
    }

    public function configSchema(): array
    {
        return [
            'alignment' => [
                'type' => 'select',
                'label' => 'Content Alignment',
                'default' => 'center',
                'options' => ['left', 'center', 'right'],
            ],
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
            'buttonColor' => [
                'type' => 'select',
                'label' => 'Button Color',
                'default' => 'white',
                'options' => ['white', 'yellow'],
            ],
        ];
    }

    public function supportedActions(): array
    {
        return ['recent', 'trending', 'byTag', 'byCategory'];
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
                'placeholder' => 'Enter large headline text',
            ],
            'title' => [
                'type' => 'text',
                'label' => 'Title',
                'placeholder' => 'Enter title',
            ],
            'image' => [
                'type' => 'media',
                'label' => 'Background Image',
                'placeholder' => 'Select or enter image URL',
            ],
            'category' => [
                'type' => 'text',
                'label' => 'Category',
                'placeholder' => 'Enter category name',
            ],
            'categoryUrl' => [
                'type' => 'text',
                'label' => 'Category URL',
                'placeholder' => 'Enter category link',
            ],
            'author' => [
                'type' => 'text',
                'label' => 'Author',
                'placeholder' => 'Enter author name',
            ],
            'date' => [
                'type' => 'text',
                'label' => 'Date',
                'placeholder' => 'Enter date',
            ],
            'url' => [
                'type' => 'text',
                'label' => 'Button URL',
                'placeholder' => 'Enter button link',
            ],
        ];
    }

    public function previewSchema(): array
    {
        return [
            'layout' => 'single',
            'areas' => [
                ['id' => 'slot-0', 'label' => 'Hero', 'width' => 'full', 'height' => 'full', 'slotIndex' => 0],
            ],
        ];
    }
}
