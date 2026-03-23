<?php

namespace Database\Factories;

use App\Models\DeliveryLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeliveryLocation>
 */
class DeliveryLocationFactory extends Factory
{
    protected $model = DeliveryLocation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $locations = ["Male'", "Hulhumale'", "Villimale'", 'Addu City', 'Boat Transfer', 'Speed Launch'];

        return [
            'uuid' => fake()->uuid(),
            'name' => ['en' => fake()->randomElement($locations)],
            'is_active' => true,
            'order' => fake()->numberBetween(0, 10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
