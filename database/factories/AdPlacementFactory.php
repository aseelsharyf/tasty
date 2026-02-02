<?php

namespace Database\Factories;

use App\Models\AdPlacement;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdPlacement>
 */
class AdPlacementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'name' => fake()->words(3, true).' Ad',
            'page_type' => AdPlacement::PAGE_TYPE_ARTICLE_DETAIL,
            'slot' => fake()->randomElement(array_keys(AdPlacement::SLOTS)),
            'category_id' => null,
            'ad_code' => '<div class="test-ad">'.fake()->sentence().'</div>',
            'is_active' => true,
            'priority' => fake()->numberBetween(0, 100),
        ];
    }

    public function forSlot(string $slot): static
    {
        return $this->state(fn () => [
            'slot' => $slot,
        ]);
    }

    public function forCategory(Category $category): static
    {
        return $this->state(fn () => [
            'category_id' => $category->id,
        ]);
    }

    public function global(): static
    {
        return $this->state(fn () => [
            'category_id' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function withPriority(int $priority): static
    {
        return $this->state(fn () => [
            'priority' => $priority,
        ]);
    }
}
