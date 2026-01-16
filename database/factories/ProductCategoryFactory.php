<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Pantry', 'Appliances', 'Tools', 'Cookware', 'Tableware', 'Ingredients', 'Gadgets', 'Storage'];
        $name = fake()->randomElement($categories);

        return [
            'uuid' => fake()->uuid(),
            'name' => ['en' => $name],
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'description' => ['en' => fake()->sentence()],
            'is_active' => true,
            'order' => fake()->numberBetween(0, 10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
