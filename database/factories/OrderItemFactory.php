<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $price = fake()->randomFloat(2, 10, 200);
        $quantity = fake()->numberBetween(1, 5);

        return [
            'uuid' => fake()->uuid(),
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_type' => 'in_house',
            'product_title' => fake()->words(3, true),
            'price' => $price,
            'quantity' => $quantity,
            'total' => $price * $quantity,
        ];
    }
}
