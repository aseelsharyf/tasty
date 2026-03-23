<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\DiscountCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscountCode>
 */
class DiscountCodeFactory extends Factory
{
    protected $model = DiscountCode::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('??##??')),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(DiscountType::cases()),
            'value' => fake()->randomFloat(2, 5, 30),
            'is_active' => true,
        ];
    }

    public function percentage(float $value = 10): static
    {
        return $this->state([
            'type' => DiscountType::Percentage,
            'value' => $value,
        ]);
    }

    public function fixed(float $value = 50): static
    {
        return $this->state([
            'type' => DiscountType::Fixed,
            'value' => $value,
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'expires_at' => now()->subDay(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }
}
