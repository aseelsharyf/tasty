<?php

namespace App\Services\Layouts\Sections;

class AddToCartSection extends AbstractSectionDefinition
{
    public function type(): string
    {
        return 'add-to-cart';
    }

    public function name(): string
    {
        return 'Add to Cart';
    }

    public function description(): string
    {
        return 'Product showcase section with grid of product cards.';
    }

    public function icon(): string
    {
        return 'i-lucide-shopping-cart';
    }

    public function slotCount(): int
    {
        return 3; // 3 product slots
    }

    public function minSlots(): int
    {
        return 1;
    }

    public function maxSlots(): int
    {
        return 9;
    }

    public function configSchema(): array
    {
        return [
            'title' => [
                'type' => 'text',
                'label' => 'Title',
                'default' => 'ADD TO CART',
            ],
            'description' => [
                'type' => 'textarea',
                'label' => 'Description',
                'default' => 'Ingredients, tools, and staples we actually use.',
            ],
            'bgColor' => [
                'type' => 'color',
                'label' => 'Background Color',
                'default' => 'white',
            ],
        ];
    }

    public function supportedActions(): array
    {
        return []; // Products are manually configured, not fetched from posts
    }

    public function slotLabels(): array
    {
        $labels = [];
        for ($i = 0; $i < $this->maxSlots(); $i++) {
            $labels[$i] = 'Product '.($i + 1);
        }

        return $labels;
    }

    public function slotSchema(): array
    {
        return [
            'title' => [
                'type' => 'text',
                'label' => 'Product Title',
                'placeholder' => 'Enter product name',
            ],
            'description' => [
                'type' => 'textarea',
                'label' => 'Description',
                'placeholder' => 'Enter product description',
            ],
            'image' => [
                'type' => 'media',
                'label' => 'Product Image',
                'placeholder' => 'Select or enter image URL',
            ],
            'imageAlt' => [
                'type' => 'text',
                'label' => 'Image Alt Text',
                'placeholder' => 'Describe the image',
            ],
            'tags' => [
                'type' => 'tags',
                'label' => 'Tags',
                'placeholder' => 'e.g., PANTRY, APPLIANCE',
            ],
            'url' => [
                'type' => 'text',
                'label' => 'Product URL',
                'placeholder' => 'Enter product link',
            ],
        ];
    }

    public function previewSchema(): array
    {
        return [
            'layout' => 'grid',
            'areas' => [
                ['id' => 'products', 'label' => '', 'width' => 'full', 'height' => 'full', 'gridCols' => 3, 'children' => [
                    ['id' => 'slot-0', 'label' => '1', 'slotIndex' => 0],
                    ['id' => 'slot-1', 'label' => '2', 'slotIndex' => 1],
                    ['id' => 'slot-2', 'label' => '3', 'slotIndex' => 2],
                ]],
            ],
        ];
    }
}
