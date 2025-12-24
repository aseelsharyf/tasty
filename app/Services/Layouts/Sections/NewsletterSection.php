<?php

namespace App\Services\Layouts\Sections;

class NewsletterSection extends AbstractSectionDefinition
{
    public function type(): string
    {
        return 'newsletter';
    }

    public function name(): string
    {
        return 'Newsletter';
    }

    public function description(): string
    {
        return 'Newsletter subscription section with email input and call-to-action.';
    }

    public function icon(): string
    {
        return 'i-lucide-mail';
    }

    public function slotCount(): int
    {
        return 0; // No content slots - static content only
    }

    public function configSchema(): array
    {
        return [
            'title' => [
                'type' => 'text',
                'label' => 'Title',
                'default' => 'COME HUNGRY, LEAVE INSPIRED. SIGN UP FOR TASTY UPDATES.',
            ],
            'placeholder' => [
                'type' => 'text',
                'label' => 'Input Placeholder',
                'default' => 'Enter your Email',
            ],
            'buttonText' => [
                'type' => 'text',
                'label' => 'Button Text',
                'default' => 'SUBSCRIBE',
            ],
            'bgColor' => [
                'type' => 'color',
                'label' => 'Background Color',
                'default' => '#F3F4F6',
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
                ['id' => 'newsletter', 'label' => 'Newsletter (No slots)', 'width' => 'full', 'height' => 'full'],
            ],
        ];
    }
}
