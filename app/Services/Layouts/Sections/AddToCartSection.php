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
        return ['recent']; // Support dynamic product loading
    }

    /**
     * Indicate this section uses products, not posts.
     */
    public function contentType(): string
    {
        return 'product';
    }

    public function slotLabels(): array
    {
        $labels = [];
        for ($i = 0; $i < $this->maxSlots(); $i++) {
            $labels[$i] = 'Product '.($i + 1);
        }

        return $labels;
    }

    /**
     * Slot schema for static content (not used for products).
     */
    public function slotSchema(): array
    {
        return [];
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

    /**
     * Override default slots to use product mode.
     *
     * @return array<int, array{index: int, mode: string, productId: int|null}>
     */
    public function defaultSlots(): array
    {
        $slots = [];

        for ($i = 0; $i < $this->slotCount(); $i++) {
            $slots[] = [
                'index' => $i,
                'mode' => 'dynamic', // Use dynamic by default to load recent products
                'productId' => null,
            ];
        }

        return $slots;
    }
}
