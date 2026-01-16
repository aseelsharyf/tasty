<?php

namespace Database\Seeders;

use App\Models\MediaItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed product categories and products.
     */
    public function run(): void
    {
        $categories = $this->createCategories();

        if ($categories->isEmpty()) {
            $this->command->warn('No categories were created. Skipping ProductSeeder.');

            return;
        }

        $tags = Tag::all();
        $mediaItems = MediaItem::images()->get();

        if ($mediaItems->isEmpty()) {
            $this->command->warn('No media items found. Products will be created without images.');
        }

        // Create products for each category
        foreach ($categories as $category) {
            $this->createProductsForCategory($category, $tags, $mediaItems);
        }

        $this->command->info('Products seeded successfully!');
    }

    /**
     * Create predefined product categories.
     *
     * @return \Illuminate\Support\Collection<int, ProductCategory>
     */
    private function createCategories()
    {
        $categoriesData = [
            [
                'name' => ['en' => 'Cookware'],
                'slug' => 'cookware',
                'description' => ['en' => 'Essential pots, pans, and cooking vessels for every kitchen'],
                'order' => 1,
            ],
            [
                'name' => ['en' => 'Kitchen Tools'],
                'slug' => 'kitchen-tools',
                'description' => ['en' => 'Knives, utensils, and gadgets to make cooking easier'],
                'order' => 2,
            ],
            [
                'name' => ['en' => 'Appliances'],
                'slug' => 'appliances',
                'description' => ['en' => 'Small appliances that transform your cooking'],
                'order' => 3,
            ],
            [
                'name' => ['en' => 'Pantry Essentials'],
                'slug' => 'pantry-essentials',
                'description' => ['en' => 'Quality ingredients and staples for your pantry'],
                'order' => 4,
            ],
            [
                'name' => ['en' => 'Tableware'],
                'slug' => 'tableware',
                'description' => ['en' => 'Beautiful plates, bowls, and serving pieces'],
                'order' => 5,
            ],
        ];

        foreach ($categoriesData as $data) {
            ProductCategory::firstOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'uuid' => fake()->uuid(),
                    'is_active' => true,
                ])
            );
        }

        return ProductCategory::all();
    }

    /**
     * Create products for a specific category.
     *
     * @param  \Illuminate\Support\Collection<int, Tag>  $tags
     * @param  \Illuminate\Support\Collection<int, MediaItem>  $mediaItems
     */
    private function createProductsForCategory(ProductCategory $category, $tags, $mediaItems): void
    {
        $products = $this->getProductsForCategory($category->slug);

        foreach ($products as $index => $productData) {
            $featuredTag = $tags->isNotEmpty() ? $tags->random() : null;
            $featuredMedia = $mediaItems->isNotEmpty() ? $mediaItems->random() : null;

            $product = Product::firstOrCreate(
                ['slug' => $productData['slug']],
                [
                    'uuid' => fake()->uuid(),
                    'title' => $productData['title'],
                    'slug' => $productData['slug'],
                    'description' => $productData['description'],
                    'product_category_id' => $category->id,
                    'featured_tag_id' => $featuredTag?->id,
                    'featured_media_id' => $featuredMedia?->id,
                    'price' => $productData['price'],
                    'compare_at_price' => $productData['compare_at_price'] ?? null,
                    'currency' => 'USD',
                    'affiliate_url' => $productData['affiliate_url'],
                    'affiliate_source' => $productData['affiliate_source'],
                    'is_active' => true,
                    'order' => $index,
                ]
            );

            // Attach random tags
            if ($tags->isNotEmpty() && $product->wasRecentlyCreated) {
                $productTags = $tags->random(min(rand(2, 4), $tags->count()));
                $product->tags()->attach($productTags->pluck('id'));
            }
        }
    }

    /**
     * Get product data for a specific category.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getProductsForCategory(string $categorySlug): array
    {
        return match ($categorySlug) {
            'cookware' => [
                [
                    'title' => ['en' => 'Lodge Cast Iron Skillet 12"'],
                    'slug' => 'lodge-cast-iron-skillet-12',
                    'description' => ['en' => 'Pre-seasoned cast iron skillet perfect for searing, baking, and everything in between. A kitchen workhorse that lasts generations.'],
                    'price' => 44.99,
                    'compare_at_price' => 54.99,
                    'affiliate_url' => 'https://amazon.com/dp/example1',
                    'affiliate_source' => 'Amazon',
                ],
                [
                    'title' => ['en' => 'Le Creuset Dutch Oven 5.5 Qt'],
                    'slug' => 'le-creuset-dutch-oven-5qt',
                    'description' => ['en' => 'Iconic enameled cast iron Dutch oven ideal for slow cooking, braising, and making soups. The gold standard of cookware.'],
                    'price' => 379.99,
                    'affiliate_url' => 'https://amazon.com/dp/example2',
                    'affiliate_source' => 'Amazon',
                ],
                [
                    'title' => ['en' => 'All-Clad Stainless Steel Fry Pan 10"'],
                    'slug' => 'all-clad-stainless-fry-pan-10',
                    'description' => ['en' => 'Professional-grade tri-ply stainless steel pan with exceptional heat distribution. Perfect for deglazing and building fond.'],
                    'price' => 149.99,
                    'compare_at_price' => 179.99,
                    'affiliate_url' => 'https://amazon.com/dp/example3',
                    'affiliate_source' => 'Amazon',
                ],
                [
                    'title' => ['en' => 'Staub Cocotte Round 4 Qt'],
                    'slug' => 'staub-cocotte-round-4qt',
                    'description' => ['en' => 'French enameled cast iron cocotte with self-basting lid spikes. Creates perfectly moist braises and stews.'],
                    'price' => 299.99,
                    'affiliate_url' => 'https://amazon.com/dp/example4',
                    'affiliate_source' => 'Amazon',
                ],
            ],
            'kitchen-tools' => [
                [
                    'title' => ['en' => 'Victorinox Fibrox Pro Chef\'s Knife 8"'],
                    'slug' => 'victorinox-fibrox-chef-knife-8',
                    'description' => ['en' => 'The best value chef\'s knife on the market. Sharp, well-balanced, and perfect for home cooks and professionals alike.'],
                    'price' => 34.99,
                    'affiliate_url' => 'https://amazon.com/dp/example5',
                    'affiliate_source' => 'Amazon',
                ],
                [
                    'title' => ['en' => 'Microplane Premium Zester'],
                    'slug' => 'microplane-premium-zester',
                    'description' => ['en' => 'The original and best zester for citrus, hard cheeses, ginger, and more. Razor-sharp blades make quick work of zesting.'],
                    'price' => 14.99,
                    'affiliate_url' => 'https://amazon.com/dp/example6',
                    'affiliate_source' => 'Amazon',
                ],
                [
                    'title' => ['en' => 'OXO Good Grips Wooden Spoon Set'],
                    'slug' => 'oxo-wooden-spoon-set',
                    'description' => ['en' => 'Set of 3 beechwood cooking spoons in essential shapes. Gentle on cookware and comfortable to hold.'],
                    'price' => 19.99,
                    'affiliate_url' => 'https://amazon.com/dp/example7',
                    'affiliate_source' => 'Amazon',
                ],
                [
                    'title' => ['en' => 'Granite Mortar and Pestle'],
                    'slug' => 'granite-mortar-pestle',
                    'description' => ['en' => 'Heavy-duty granite mortar and pestle for grinding spices, making curry pastes, and crushing garlic. Essential for authentic flavors.'],
                    'price' => 39.99,
                    'compare_at_price' => 49.99,
                    'affiliate_url' => 'https://amazon.com/dp/example8',
                    'affiliate_source' => 'Amazon',
                ],
                [
                    'title' => ['en' => 'Bamboo Cutting Board Set'],
                    'slug' => 'bamboo-cutting-board-set',
                    'description' => ['en' => 'Set of 3 sustainable bamboo cutting boards in various sizes. Knife-friendly and naturally antimicrobial.'],
                    'price' => 29.99,
                    'affiliate_url' => 'https://amazon.com/dp/example9',
                    'affiliate_source' => 'Amazon',
                ],
            ],
            'appliances' => [
                [
                    'title' => ['en' => 'Instant Pot Duo 6 Qt'],
                    'slug' => 'instant-pot-duo-6qt',
                    'description' => ['en' => '7-in-1 electric pressure cooker that also slow cooks, sautés, steams, and more. Perfect for busy weeknight dinners.'],
                    'price' => 89.99,
                    'compare_at_price' => 99.99,
                    'affiliate_url' => 'https://amazon.com/dp/example10',
                    'affiliate_source' => 'Amazon',
                ],
                [
                    'title' => ['en' => 'KitchenAid Artisan Stand Mixer 5 Qt'],
                    'slug' => 'kitchenaid-artisan-stand-mixer',
                    'description' => ['en' => 'The iconic stand mixer for serious bakers. 10 speeds, planetary mixing action, and dozens of attachment options.'],
                    'price' => 449.99,
                    'affiliate_url' => 'https://amazon.com/dp/example11',
                    'affiliate_source' => 'Amazon',
                ],
                [
                    'title' => ['en' => 'Vitamix E310 Explorian Blender'],
                    'slug' => 'vitamix-e310-blender',
                    'description' => ['en' => 'Professional-grade blender for silky smooth soups, smoothies, and sauces. Variable speed control and self-cleaning.'],
                    'price' => 349.99,
                    'compare_at_price' => 399.99,
                    'affiliate_url' => 'https://amazon.com/dp/example12',
                    'affiliate_source' => 'Amazon',
                ],
                [
                    'title' => ['en' => 'Ninja Foodi Air Fryer'],
                    'slug' => 'ninja-foodi-air-fryer',
                    'description' => ['en' => 'Dual-zone air fryer that can cook two foods at once. Crispy results with up to 75% less fat than traditional frying.'],
                    'price' => 179.99,
                    'affiliate_url' => 'https://amazon.com/dp/example13',
                    'affiliate_source' => 'Amazon',
                ],
            ],
            'pantry-essentials' => [
                [
                    'title' => ['en' => 'California Olive Ranch Extra Virgin Olive Oil'],
                    'slug' => 'california-olive-ranch-evoo',
                    'description' => ['en' => 'Fresh, peppery California olive oil perfect for finishing dishes, dressings, and everyday cooking.'],
                    'price' => 16.99,
                    'affiliate_url' => 'https://amazon.com/dp/example14',
                    'affiliate_source' => 'Amazon',
                ],
                [
                    'title' => ['en' => 'Red Boat Fish Sauce'],
                    'slug' => 'red-boat-fish-sauce',
                    'description' => ['en' => 'First-press, single-origin fish sauce from Vietnam. Adds incredible umami depth to any dish.'],
                    'price' => 12.99,
                    'affiliate_url' => 'https://amazon.com/dp/example15',
                    'affiliate_source' => 'Amazon',
                ],
                [
                    'title' => ['en' => 'Aroy-D Coconut Cream'],
                    'slug' => 'aroy-d-coconut-cream',
                    'description' => ['en' => 'Rich, thick coconut cream without additives. Essential for authentic curries and Southeast Asian desserts.'],
                    'price' => 3.99,
                    'affiliate_url' => 'https://amazon.com/dp/example16',
                    'affiliate_source' => 'Amazon',
                ],
                [
                    'title' => ['en' => 'Maldon Sea Salt Flakes'],
                    'slug' => 'maldon-sea-salt-flakes',
                    'description' => ['en' => 'Delicate pyramid-shaped salt crystals for finishing dishes. The satisfying crunch that elevates any plate.'],
                    'price' => 8.99,
                    'affiliate_url' => 'https://amazon.com/dp/example17',
                    'affiliate_source' => 'Amazon',
                ],
                [
                    'title' => ['en' => 'Diaspora Co. Turmeric Powder'],
                    'slug' => 'diaspora-turmeric-powder',
                    'description' => ['en' => 'Single-origin, high-curcumin turmeric with vibrant color and complex flavor. Farm-direct and sustainably sourced.'],
                    'price' => 14.99,
                    'affiliate_url' => 'https://diasporaco.com',
                    'affiliate_source' => 'Direct',
                ],
            ],
            'tableware' => [
                [
                    'title' => ['en' => 'Heath Ceramics Coupe Dinner Plate'],
                    'slug' => 'heath-ceramics-coupe-plate',
                    'description' => ['en' => 'Handcrafted stoneware plate in classic California modern style. Each piece is unique with subtle variations.'],
                    'price' => 48.00,
                    'affiliate_url' => 'https://heathceramics.com',
                    'affiliate_source' => 'Direct',
                ],
                [
                    'title' => ['en' => 'East Fork Everyday Bowl'],
                    'slug' => 'east-fork-everyday-bowl',
                    'description' => ['en' => 'Versatile, hand-thrown bowl perfect for grain bowls, soups, and pasta. Made in Asheville, NC.'],
                    'price' => 40.00,
                    'affiliate_url' => 'https://eastfork.com',
                    'affiliate_source' => 'Direct',
                ],
                [
                    'title' => ['en' => 'Material Kitchen Good Knives Set'],
                    'slug' => 'material-good-knives-set',
                    'description' => ['en' => 'Set of 3 essential knives with reBoard knife block. Thoughtfully designed for modern home cooks.'],
                    'price' => 185.00,
                    'affiliate_url' => 'https://materialkitchen.com',
                    'affiliate_source' => 'Direct',
                ],
            ],
            default => [],
        };
    }
}
