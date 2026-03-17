<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'order_number' => 'TST-'.now()->format('Ymd').'-'.fake()->unique()->numberBetween(1000, 9999),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'subtotal' => fake()->randomFloat(2, 50, 1000),
            'total' => fake()->randomFloat(2, 50, 1000),
            'currency' => 'MVR',
            'contact_person' => fake()->name(),
            'contact_number' => fake()->phoneNumber(),
            'email' => fake()->optional(0.7)->safeEmail(),
            'address' => fake()->address(),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['status' => 'accepted', 'accepted_at' => now()]);
    }

    public function paid(): static
    {
        return $this->state(fn () => ['payment_status' => 'paid', 'paid_at' => now()]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed', 'completed_at' => now()]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled', 'cancelled_at' => now()]);
    }

    public function withAffiliateProducts(): static
    {
        return $this->state(fn () => ['has_affiliate_products' => true]);
    }
}
