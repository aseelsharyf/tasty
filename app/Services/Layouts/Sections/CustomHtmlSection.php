<?php

namespace App\Services\Layouts\Sections;

class CustomHtmlSection extends AbstractSectionDefinition
{
    public function type(): string
    {
        return 'custom-html';
    }

    public function name(): string
    {
        return 'Custom HTML';
    }

    public function description(): string
    {
        return 'A flexible section for adding custom HTML content.';
    }

    public function icon(): string
    {
        return 'i-lucide-code';
    }

    public function slotCount(): int
    {
        return 0; // No content slots - just HTML input
    }

    public function configSchema(): array
    {
        return [
            'html' => [
                'type' => 'textarea',
                'label' => 'HTML Content',
                'default' => '',
                'rows' => 10,
                'inline' => true,
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
                ['id' => 'html', 'label' => 'Custom HTML', 'width' => 'full', 'height' => 'full'],
            ],
        ];
    }
}
