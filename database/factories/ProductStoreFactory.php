<?php

namespace Database\Factories;

use App\Models\ProductStore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductStore>
 */
class ProductStoreFactory extends Factory
{
    protected $model = ProductStore::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $stores = [
            'Amazon',
            'Target',
            'Walmart',
            'Williams Sonoma',
            'Sur La Table',
            'Crate & Barrel',
            'Bed Bath & Beyond',
            'Costco',
            'Direct Store',
        ];

        $businessTypes = ['retailer', 'wholesaler', 'manufacturer', 'distributor', 'online_only'];

        return [
            'uuid' => fake()->uuid(),
            'name' => fake()->randomElement($stores),
            'business_type' => fake()->optional(0.8)->randomElement($businessTypes),
            'address' => fake()->optional(0.7)->address(),
            'location_label' => fake()->optional(0.5)->city(),
            'hotline' => fake()->optional(0.6)->phoneNumber(),
            'contact_email' => fake()->optional(0.7)->companyEmail(),
            'website_url' => fake()->optional(0.6)->url(),
            'is_active' => true,
            'order' => fake()->numberBetween(0, 10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withName(string $name): static
    {
        return $this->state(fn () => ['name' => $name]);
    }

    public function retailer(): static
    {
        return $this->state(fn () => ['business_type' => 'retailer']);
    }

    public function wholesaler(): static
    {
        return $this->state(fn () => ['business_type' => 'wholesaler']);
    }

    public function onlineOnly(): static
    {
        return $this->state(fn () => ['business_type' => 'online_only']);
    }
}
