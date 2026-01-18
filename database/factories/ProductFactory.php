<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStore;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titles = [
            'Cast Iron Skillet',
            'Olive Oil - Extra Virgin',
            'Chef\'s Knife',
            'Coconut Cream',
            'Bamboo Cutting Board',
            'Fish Sauce',
            'Stand Mixer',
            'Mortar and Pestle',
            'Dutch Oven',
            'Instant Pot',
            'Air Fryer',
            'Blender',
        ];
        $title = fake()->randomElement($titles);

        $brands = ['Lodge', 'Le Creuset', 'KitchenAid', 'Vitamix', 'OXO', 'Staub', 'All-Clad', 'Ninja', null];
        $availabilities = ['in_stock', 'in_stock', 'in_stock', 'out_of_stock', 'pre_order', 'discontinued'];

        return [
            'uuid' => fake()->uuid(),
            'title' => ['en' => $title],
            'slug' => Str::slug($title).'-'.fake()->unique()->randomNumber(4),
            'description' => ['en' => fake()->paragraph()],
            'short_description' => ['en' => fake()->sentence()],
            'brand' => fake()->optional(0.7)->randomElement($brands),
            'product_category_id' => ProductCategory::factory(),
            'product_store_id' => ProductStore::factory(),
            'price' => fake()->randomFloat(2, 10, 500),
            'currency' => 'USD',
            'availability' => fake()->randomElement($availabilities),
            'affiliate_url' => fake()->url(),
            'is_active' => true,
            'is_featured' => fake()->boolean(20),
            'order' => fake()->numberBetween(0, 10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function inStock(): static
    {
        return $this->state(fn () => ['availability' => 'in_stock']);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['availability' => 'out_of_stock']);
    }

    public function preOrder(): static
    {
        return $this->state(fn () => ['availability' => 'pre_order']);
    }

    public function discontinued(): static
    {
        return $this->state(fn () => ['availability' => 'discontinued']);
    }

    public function forCategory(ProductCategory $category): static
    {
        return $this->state(fn () => ['product_category_id' => $category->id]);
    }

    public function forStore(ProductStore $store): static
    {
        return $this->state(fn () => ['product_store_id' => $store->id]);
    }

    public function withoutStore(): static
    {
        return $this->state(fn () => ['product_store_id' => null]);
    }

    public function withBrand(string $brand): static
    {
        return $this->state(fn () => ['brand' => $brand]);
    }

    public function withPrice(float $price): static
    {
        return $this->state(fn () => ['price' => $price]);
    }

    public function withDiscount(float $originalPrice, float $salePrice): static
    {
        return $this->state(fn () => [
            'price' => $salePrice,
            'compare_at_price' => $originalPrice,
        ]);
    }

    public function trackingInventory(int $stock = 10): static
    {
        return $this->state(fn () => [
            'track_inventory' => true,
            'stock_quantity' => $stock,
        ]);
    }
}
