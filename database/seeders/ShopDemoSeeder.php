<?php

namespace Database\Seeders;

use App\Models\DeliveryLocation;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStore;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class ShopDemoSeeder extends Seeder
{
    /**
     * Seed demo products with variants, delivery locations, and shop settings for testing.
     */
    public function run(): void
    {
        // Create categories
        $kitchenware = ProductCategory::firstOrCreate(
            ['slug' => 'kitchenware'],
            ['name' => ['en' => 'Kitchenware'], 'is_active' => true, 'order' => 1]
        );

        $groceries = ProductCategory::firstOrCreate(
            ['slug' => 'groceries'],
            ['name' => ['en' => 'Groceries'], 'is_active' => true, 'order' => 2]
        );

        $appliances = ProductCategory::firstOrCreate(
            ['slug' => 'appliances'],
            ['name' => ['en' => 'Appliances'], 'is_active' => true, 'order' => 3]
        );

        // Create a store
        $store = ProductStore::firstOrCreate(
            ['slug' => 'tasty-store'],
            ['name' => 'Tasty Store', 'is_active' => true, 'order' => 1]
        );

        // Referral products (existing behavior)
        Product::factory()
            ->referral()
            ->forCategory($kitchenware)
            ->forStore($store)
            ->create([
                'title' => ['en' => 'Le Creuset Dutch Oven'],
                'price' => 350.00,
                'currency' => 'USD',
                'affiliate_url' => 'https://example.com/le-creuset',
            ]);

        Product::factory()
            ->referral()
            ->forCategory($kitchenware)
            ->forStore($store)
            ->create([
                'title' => ['en' => 'Victorinox Chef Knife'],
                'price' => 45.00,
                'currency' => 'USD',
                'affiliate_url' => 'https://example.com/victorinox',
            ]);

        // In-house products (can be purchased directly)
        $oliveoil = Product::factory()
            ->inHouse()
            ->forCategory($groceries)
            ->forStore($store)
            ->trackingInventory(50)
            ->create([
                'title' => ['en' => 'Extra Virgin Olive Oil'],
                'short_description' => ['en' => 'Premium cold-pressed olive oil from the Mediterranean'],
                'price' => 25.00,
                'currency' => 'MVR',
            ]);

        // Add variants to olive oil
        ProductVariant::factory()->forProduct($oliveoil)->create([
            'name' => '250ml',
            'price' => 15.00,
            'sku' => 'OIL-250',
            'stock_quantity' => 30,
            'order' => 0,
        ]);
        ProductVariant::factory()->forProduct($oliveoil)->create([
            'name' => '500ml',
            'price' => 25.00,
            'sku' => 'OIL-500',
            'stock_quantity' => 20,
            'order' => 1,
        ]);
        ProductVariant::factory()->forProduct($oliveoil)->create([
            'name' => '1 Litre',
            'price' => 40.00,
            'sku' => 'OIL-1L',
            'stock_quantity' => 10,
            'order' => 2,
        ]);

        $honey = Product::factory()
            ->inHouse()
            ->forCategory($groceries)
            ->forStore($store)
            ->trackingInventory(25)
            ->create([
                'title' => ['en' => 'Maldivian Wild Honey'],
                'short_description' => ['en' => 'Pure wild honey harvested from local islands'],
                'price' => 35.00,
                'currency' => 'MVR',
            ]);

        ProductVariant::factory()->forProduct($honey)->create([
            'name' => 'Small Jar (200g)',
            'price' => 35.00,
            'sku' => 'HONEY-S',
            'stock_quantity' => 15,
            'order' => 0,
        ]);
        ProductVariant::factory()->forProduct($honey)->create([
            'name' => 'Large Jar (500g)',
            'price' => 75.00,
            'sku' => 'HONEY-L',
            'stock_quantity' => 10,
            'order' => 1,
        ]);

        Product::factory()
            ->inHouse()
            ->forCategory($groceries)
            ->forStore($store)
            ->trackingInventory(100)
            ->create([
                'title' => ['en' => 'Coconut Cream'],
                'short_description' => ['en' => 'Fresh Maldivian coconut cream - perfect for curries'],
                'price' => 8.50,
                'currency' => 'MVR',
                'sku' => 'CC-001',
            ]);

        // Affiliate product (sold through partner, needs manual acceptance)
        Product::factory()
            ->affiliate()
            ->forCategory($appliances)
            ->forStore($store)
            ->create([
                'title' => ['en' => 'KitchenAid Stand Mixer'],
                'short_description' => ['en' => 'Professional-grade stand mixer for serious bakers'],
                'price' => 4500.00,
                'currency' => 'MVR',
            ]);

        Product::factory()
            ->affiliate()
            ->forCategory($appliances)
            ->forStore($store)
            ->create([
                'title' => ['en' => 'Ninja Air Fryer'],
                'short_description' => ['en' => 'Healthy cooking with less oil'],
                'price' => 1200.00,
                'currency' => 'MVR',
            ]);

        // Create delivery locations
        $locations = [
            ["Male'", 1],
            ["Hulhumale'", 2],
            ["Villimale'", 3],
            ['Addu City', 4],
            ['Boat Transfer (Other Islands)', 5],
        ];

        foreach ($locations as [$name, $order]) {
            DeliveryLocation::firstOrCreate(
                ['name->en' => $name],
                ['name' => ['en' => $name], 'is_active' => true, 'order' => $order]
            );
        }

        // Set up shop settings
        Setting::setBankAccounts([
            [
                'bank_name' => 'Bank of Maldives (BML)',
                'account_name' => 'Tasty Maldives Pvt Ltd',
                'account_number' => '7730000123456',
                'currency' => 'MVR',
            ],
            [
                'bank_name' => 'Bank of Maldives (BML)',
                'account_name' => 'Tasty Maldives Pvt Ltd',
                'account_number' => '7730000123457',
                'currency' => 'USD',
            ],
        ]);

        Setting::setPaymentMethods([
            ['key' => 'bml_gateway', 'name' => 'BML Gateway', 'type' => 'gateway', 'is_active' => false],
            ['key' => 'bank_transfer', 'name' => 'Bank Transfer', 'type' => 'bank_transfer', 'is_active' => true],
            ['key' => 'ooredoo_mfaisaa', 'name' => 'Ooredoo m-Faisaa', 'type' => 'online', 'is_active' => false],
            ['key' => 'dhiraagu_pay', 'name' => 'Dhiraagu Pay', 'type' => 'online', 'is_active' => false],
        ]);

        $this->command->info('Shop demo data seeded: 7 products (2 referral, 3 in-house, 2 affiliate), 5 delivery locations, bank accounts & payment methods configured.');
    }
}
