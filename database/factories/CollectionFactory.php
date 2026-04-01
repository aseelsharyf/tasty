<?php

namespace Database\Factories;

use App\Models\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Collection>
 */
class CollectionFactory extends Factory
{
    protected $model = Collection::class;

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
            'description' => ['en' => fake()->sentence()],
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'is_active' => true,
            'order' => 0,
        ];
    }

    /**
     * Indicate the collection is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
