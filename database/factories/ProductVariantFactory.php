<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $variants = ['Small', 'Medium', 'Large', 'XL', '250ml', '500ml', '1L', 'Pack of 3', 'Pack of 6'];

        return [
            'uuid' => fake()->uuid(),
            'product_id' => Product::factory(),
            'name' => fake()->randomElement($variants),
            'price' => fake()->randomFloat(2, 5, 200),
            'sku' => fake()->optional(0.7)->bothify('VAR-####-??'),
            'stock_quantity' => fake()->numberBetween(0, 100),
            'is_active' => true,
            'order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withPrice(float $price): static
    {
        return $this->state(fn () => ['price' => $price]);
    }

    public function forProduct(Product $product): static
    {
        return $this->state(fn () => ['product_id' => $product->id]);
    }
}
