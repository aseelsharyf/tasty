<?php

namespace App\Services\Layouts\Sections;

class AdSection extends AbstractSectionDefinition
{
    public function type(): string
    {
        return 'ad';
    }

    public function name(): string
    {
        return 'Advertisement';
    }

    public function description(): string
    {
        return 'Display an advertisement slot with configurable size and placement.';
    }

    public function icon(): string
    {
        return 'i-lucide-megaphone';
    }

    public function slotCount(): int
    {
        return 0; // No content slots - configuration only
    }

    public function configSchema(): array
    {
        return [
            'adSlot' => [
                'type' => 'text',
                'label' => 'Ad Slot ID',
                'default' => '',
            ],
            'size' => [
                'type' => 'select',
                'label' => 'Preset Size',
                'options' => ['auto', 'leaderboard', 'medium-rectangle', 'large-rectangle', 'half-page', 'billboard', 'mobile-banner', 'large-mobile-banner'],
                'default' => 'auto',
            ],
            'bgColor' => [
                'type' => 'color',
                'label' => 'Background Color',
                'default' => '#F7F7F7',
            ],
            'paddingTop' => [
                'type' => 'select',
                'label' => 'Top Padding',
                'options' => ['none', 'small', 'medium', 'large'],
                'default' => 'medium',
            ],
            'paddingBottom' => [
                'type' => 'select',
                'label' => 'Bottom Padding',
                'options' => ['none', 'small', 'medium', 'large'],
                'default' => 'medium',
            ],
        ];
    }

    public function supportedActions(): array
    {
        return []; // No dynamic content
    }

    public function previewSchema(): array
    {
        return [
            'layout' => 'single',
            'areas' => [
                ['id' => 'ad', 'label' => 'Ad (No slots)', 'width' => 'full', 'height' => 'full'],
            ],
        ];
    }
}
