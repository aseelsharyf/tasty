<?php

namespace App\Services;

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Str;

class CartService
{
    protected const SESSION_KEY = 'cart';

    /**
     * Add a product to the cart.
     *
     * @return array{success: bool, message: string}
     */
    public function add(int $productId, int $quantity = 1, ?int $variantId = null): array
    {
        $product = Product::find($productId);

        if (! $product) {
            return ['success' => false, 'message' => 'Product not found.'];
        }

        if ($product->product_type === ProductType::Referral) {
            return ['success' => false, 'message' => 'Referral products cannot be added to cart.'];
        }

        if (! $product->is_active) {
            return ['success' => false, 'message' => 'This product is not available.'];
        }

        // Validate variant if product has variants
        if ($product->variants()->exists() && ! $variantId) {
            return ['success' => false, 'message' => 'Please select a variant.'];
        }

        if ($variantId) {
            $variant = ProductVariant::where('id', $variantId)
                ->where('product_id', $productId)
                ->active()
                ->first();

            if (! $variant) {
                return ['success' => false, 'message' => 'Selected variant is not available.'];
            }

            if (! $variant->isInStock()) {
                return ['success' => false, 'message' => 'Selected variant is out of stock.'];
            }
        } elseif (! $product->isInStock()) {
            return ['success' => false, 'message' => 'This product is out of stock.'];
        }

        $items = $this->getItems();
        $cartItemId = Str::uuid()->toString();

        // Check if same product/variant already in cart
        foreach ($items as &$item) {
            if ($item['product_id'] === $productId && $item['variant_id'] === $variantId) {
                $item['quantity'] += $quantity;

                return $this->saveAndReturn($items, 'Product quantity updated in cart.');
            }
        }

        $items[] = [
            'id' => $cartItemId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
        ];

        return $this->saveAndReturn($items, 'Product added to cart.');
    }

    /**
     * Update quantity of a cart item.
     */
    public function update(string $cartItemId, int $quantity): array
    {
        $items = $this->getItems();

        foreach ($items as &$item) {
            if ($item['id'] === $cartItemId) {
                if ($quantity <= 0) {
                    return $this->remove($cartItemId);
                }

                $item['quantity'] = $quantity;

                return $this->saveAndReturn($items, 'Cart updated.');
            }
        }

        return ['success' => false, 'message' => 'Cart item not found.'];
    }

    /**
     * Remove an item from the cart.
     */
    public function remove(string $cartItemId): array
    {
        $items = array_values(array_filter(
            $this->getItems(),
            fn ($item) => $item['id'] !== $cartItemId
        ));

        return $this->saveAndReturn($items, 'Item removed from cart.');
    }

    /**
     * Clear the entire cart.
     */
    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * Get all cart items with product data.
     *
     * @return array<int, array{id: string, product_id: int, variant_id: ?int, quantity: int}>
     */
    public function getItems(): array
    {
        return session()->get(self::SESSION_KEY, []);
    }

    /**
     * Get cart items with loaded product/variant data for display.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getItemsWithProducts(): array
    {
        $items = $this->getItems();
        $result = [];

        $productIds = array_unique(array_column($items, 'product_id'));
        $products = Product::whereIn('id', $productIds)
            ->with(['featuredMedia.media', 'variants'])
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);
            if (! $product) {
                continue;
            }

            $variant = $item['variant_id']
                ? $product->variants->firstWhere('id', $item['variant_id'])
                : null;

            $price = $variant ? (float) $variant->price : (float) $product->price;

            $result[] = [
                'id' => $item['id'],
                'product_id' => $product->id,
                'product' => [
                    'id' => $product->id,
                    'title' => $product->title,
                    'slug' => $product->slug,
                    'featured_image_url' => $product->featured_image_url,
                    'product_type' => $product->product_type?->value,
                    'currency' => $product->currency,
                ],
                'variant_id' => $item['variant_id'],
                'variant' => $variant ? [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'price' => $variant->price,
                ] : null,
                'quantity' => $item['quantity'],
                'price' => $price,
                'total' => $price * $item['quantity'],
            ];
        }

        return $result;
    }

    /**
     * Get the total price of all items in the cart.
     */
    public function getTotal(): float
    {
        return array_sum(array_column($this->getItemsWithProducts(), 'total'));
    }

    /**
     * Get the total number of items in the cart.
     */
    public function getItemCount(): int
    {
        return array_sum(array_column($this->getItems(), 'quantity'));
    }

    /**
     * Check if the cart is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->getItems());
    }

    /**
     * @return array{success: bool, message: string}
     */
    private function saveAndReturn(array $items, string $message): array
    {
        session()->put(self::SESSION_KEY, $items);

        return ['success' => true, 'message' => $message];
    }
}
