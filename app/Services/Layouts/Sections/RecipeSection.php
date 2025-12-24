<?php

namespace App\Services\Layouts\Sections;

class RecipeSection extends AbstractSectionDefinition
{
    public function type(): string
    {
        return 'recipe';
    }

    public function name(): string
    {
        return 'Recipes';
    }

    public function description(): string
    {
        return 'Recipe cards section with featured recipe and grid layout.';
    }

    public function icon(): string
    {
        return 'i-lucide-chef-hat';
    }

    public function slotCount(): int
    {
        return 4; // 1 featured + 3 grid
    }

    public function minSlots(): int
    {
        return 2; // 1 featured + at least 1 grid
    }

    public function maxSlots(): int
    {
        return 12; // 1 featured + up to 11 grid
    }

    public function configSchema(): array
    {
        return [
            'showIntro' => [
                'type' => 'toggle',
                'label' => 'Show Intro Card',
                'default' => true,
            ],
            'introImage' => [
                'type' => 'media',
                'label' => 'Intro Image',
                'default' => '',
            ],
            'introImageAlt' => [
                'type' => 'text',
                'label' => 'Intro Image Alt Text',
                'default' => '',
            ],
            'titleSmall' => [
                'type' => 'text',
                'label' => 'Small Title',
                'default' => 'Everyday',
            ],
            'titleLarge' => [
                'type' => 'text',
                'label' => 'Large Title',
                'default' => 'COOKING',
            ],
            'description' => [
                'type' => 'textarea',
                'label' => 'Description',
                'default' => '',
            ],
            'bgColor' => [
                'type' => 'color',
                'label' => 'Background Color',
                'default' => 'yellow',
            ],
            'gradient' => [
                'type' => 'select',
                'label' => 'Gradient Direction',
                'default' => 'top',
                'options' => ['top', 'bottom', 'none'],
            ],
            'mobileLayout' => [
                'type' => 'select',
                'label' => 'Mobile Layout',
                'default' => 'grid',
                'options' => ['scroll', 'grid'],
            ],
            'showDividers' => [
                'type' => 'toggle',
                'label' => 'Show Dividers',
                'default' => false,
            ],
            'dividerColor' => [
                'type' => 'select',
                'label' => 'Divider Color',
                'default' => 'white',
                'options' => ['white', 'gray'],
            ],
            'count' => [
                'type' => 'number',
                'label' => 'Number of Posts',
                'default' => 3,
            ],
        ];
    }

    public function supportedActions(): array
    {
        return ['recent', 'trending', 'byTag', 'byCategory'];
    }

    public function slotLabels(): array
    {
        $labels = [0 => 'Featured Recipe'];
        for ($i = 1; $i < $this->maxSlots(); $i++) {
            $labels[$i] = 'Recipe '.($i + 1);
        }

        return $labels;
    }

    public function previewSchema(): array
    {
        return [
            'layout' => 'featured-grid',
            'areas' => [
                ['id' => 'intro', 'label' => 'Intro', 'width' => '1/4', 'height' => 'full'],
                ['id' => 'featured', 'label' => 'Featured', 'width' => '1/4', 'height' => 'full', 'slotIndex' => 0],
                ['id' => 'grid', 'label' => '', 'width' => '1/2', 'height' => 'full', 'gridCols' => 2, 'children' => [
                    ['id' => 'slot-1', 'label' => '1', 'slotIndex' => 1],
                    ['id' => 'slot-2', 'label' => '2', 'slotIndex' => 2],
                    ['id' => 'slot-3', 'label' => '3', 'slotIndex' => 3],
                ]],
            ],
        ];
    }
}
