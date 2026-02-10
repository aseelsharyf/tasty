<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Badge>
 */
class BadgeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->word();

        return [
            'uuid' => fake()->uuid(),
            'name' => ['en' => $name],
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'icon' => null,
            'color' => fake()->randomElement(['primary', 'success', 'warning', 'error', 'info', 'neutral']),
            'description' => ['en' => fake()->sentence()],
            'is_active' => true,
            'order' => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the badge is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
