<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
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

        return [
            'uuid' => fake()->uuid(),
            'title' => ['en' => $title],
            'slug' => Str::slug($title).'-'.fake()->unique()->randomNumber(4),
            'description' => ['en' => fake()->paragraph()],
            'product_category_id' => ProductCategory::factory(),
            'price' => fake()->randomFloat(2, 10, 500),
            'currency' => 'USD',
            'affiliate_url' => fake()->url(),
            'affiliate_source' => fake()->randomElement(['Amazon', 'Partner Store', 'Direct', null]),
            'is_active' => true,
            'order' => fake()->numberBetween(0, 10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function forCategory(ProductCategory $category): static
    {
        return $this->state(fn () => ['product_category_id' => $category->id]);
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
