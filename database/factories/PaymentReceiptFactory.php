<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\PaymentReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentReceiptFactory extends Factory
{
    protected $model = PaymentReceipt::class;

    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'order_id' => Order::factory(),
            'file_path' => 'receipts/'.fake()->uuid().'.jpg',
            'original_filename' => fake()->word().'.jpg',
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => ['verified_at' => now()]);
    }
}
