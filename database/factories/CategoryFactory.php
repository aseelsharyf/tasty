<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'uuid' => fake()->uuid(),
            'name' => ['en' => $name],
            'slug' => \Illuminate\Support\Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'description' => ['en' => fake()->optional()->sentence()],
            'parent_id' => null,
            'order' => 0,
        ];
    }

    public function withParent(Category $parent): static
    {
        return $this->state(fn () => [
            'parent_id' => $parent->id,
        ]);
    }
}
