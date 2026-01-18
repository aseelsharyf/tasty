<?php

namespace Database\Seeders;

use App\Models\MediaItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStore;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed product stores, categories (with subcategories), and products.
     */
    public function run(): void
    {
        // Clear existing data for fresh seeding
        Product::query()->delete();
        ProductCategory::query()->delete();
        ProductStore::query()->delete();

        $stores = $this->createStores();
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
            $this->createProductsForCategory($category, $tags, $mediaItems, $stores);
        }

        $this->command->info('Products seeded successfully!');
    }

    /**
     * Create predefined product stores (clients).
     *
     * @return \Illuminate\Support\Collection<int, ProductStore>
     */
    private function createStores()
    {
        $storesData = [
            [
                'name' => 'Amazon',
                'business_type' => 'retailer',
                'address' => '410 Terry Ave N, Seattle, WA 98109, USA',
                'location_label' => 'Seattle, USA',
                'hotline' => '1-888-280-4331',
                'contact_email' => 'seller-support@amazon.com',
                'website_url' => 'https://www.amazon.com',
                'order' => 1,
            ],
            [
                'name' => 'Williams Sonoma',
                'business_type' => 'retailer',
                'address' => '3250 Van Ness Ave, San Francisco, CA 94109, USA',
                'location_label' => 'San Francisco, USA',
                'hotline' => '1-877-812-6235',
                'contact_email' => 'customerservice@williams-sonoma.com',
                'website_url' => 'https://www.williams-sonoma.com',
                'order' => 2,
            ],
            [
                'name' => 'Sur La Table',
                'business_type' => 'retailer',
                'address' => '6100 4th Ave S, Seattle, WA 98108, USA',
                'location_label' => 'Seattle, USA',
                'hotline' => '1-800-243-0852',
                'contact_email' => 'help@surlatable.com',
                'website_url' => 'https://www.surlatable.com',
                'order' => 3,
            ],
            [
                'name' => 'Lodge Cast Iron',
                'business_type' => 'manufacturer',
                'address' => '220 East 3rd Street, South Pittsburg, TN 37380, USA',
                'location_label' => 'Tennessee, USA',
                'hotline' => '1-423-837-7181',
                'contact_email' => 'info@lodgecastiron.com',
                'website_url' => 'https://www.lodgecastiron.com',
                'order' => 4,
            ],
            [
                'name' => 'Diaspora Co.',
                'business_type' => 'manufacturer',
                'address' => null,
                'location_label' => 'Oakland, USA',
                'hotline' => null,
                'contact_email' => 'hello@diasporaco.com',
                'website_url' => 'https://www.diasporaco.com',
                'order' => 5,
            ],
            [
                'name' => 'Heath Ceramics',
                'business_type' => 'manufacturer',
                'address' => '400 Gate 5 Road, Sausalito, CA 94965, USA',
                'location_label' => 'Sausalito, USA',
                'hotline' => '1-415-332-3732',
                'contact_email' => 'info@heathceramics.com',
                'website_url' => 'https://www.heathceramics.com',
                'order' => 6,
            ],
        ];

        foreach ($storesData as $data) {
            ProductStore::create(array_merge($data, [
                'uuid' => fake()->uuid(),
                'is_active' => true,
            ]));
        }

        return ProductStore::all();
    }

    /**
     * Create predefined product categories with subcategories.
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
                'children' => [
                    ['name' => ['en' => 'Skillets & Frying Pans'], 'slug' => 'skillets-frying-pans', 'order' => 1],
                    ['name' => ['en' => 'Dutch Ovens'], 'slug' => 'dutch-ovens', 'order' => 2],
                    ['name' => ['en' => 'Saucepans'], 'slug' => 'saucepans', 'order' => 3],
                ],
            ],
            [
                'name' => ['en' => 'Kitchen Tools'],
                'slug' => 'kitchen-tools',
                'description' => ['en' => 'Knives, utensils, and gadgets to make cooking easier'],
                'order' => 2,
                'children' => [
                    ['name' => ['en' => 'Knives'], 'slug' => 'knives', 'order' => 1],
                    ['name' => ['en' => 'Utensils'], 'slug' => 'utensils', 'order' => 2],
                    ['name' => ['en' => 'Cutting Boards'], 'slug' => 'cutting-boards', 'order' => 3],
                ],
            ],
            [
                'name' => ['en' => 'Appliances'],
                'slug' => 'appliances',
                'description' => ['en' => 'Small appliances that transform your cooking'],
                'order' => 3,
                'children' => [
                    ['name' => ['en' => 'Blenders'], 'slug' => 'blenders', 'order' => 1],
                    ['name' => ['en' => 'Stand Mixers'], 'slug' => 'stand-mixers', 'order' => 2],
                    ['name' => ['en' => 'Air Fryers'], 'slug' => 'air-fryers', 'order' => 3],
                ],
            ],
            [
                'name' => ['en' => 'Pantry Essentials'],
                'slug' => 'pantry-essentials',
                'description' => ['en' => 'Quality ingredients and staples for your pantry'],
                'order' => 4,
                'children' => [
                    ['name' => ['en' => 'Oils & Vinegars'], 'slug' => 'oils-vinegars', 'order' => 1],
                    ['name' => ['en' => 'Spices'], 'slug' => 'spices', 'order' => 2],
                    ['name' => ['en' => 'Sauces'], 'slug' => 'sauces', 'order' => 3],
                ],
            ],
            [
                'name' => ['en' => 'Tableware'],
                'slug' => 'tableware',
                'description' => ['en' => 'Beautiful plates, bowls, and serving pieces'],
                'order' => 5,
                'children' => [
                    ['name' => ['en' => 'Plates & Bowls'], 'slug' => 'plates-bowls', 'order' => 1],
                    ['name' => ['en' => 'Serveware'], 'slug' => 'serveware', 'order' => 2],
                ],
            ],
        ];

        foreach ($categoriesData as $data) {
            $children = $data['children'] ?? [];
            unset($data['children']);

            $parent = ProductCategory::create(array_merge($data, [
                'uuid' => fake()->uuid(),
                'is_active' => true,
            ]));

            // Create child categories
            foreach ($children as $childData) {
                ProductCategory::create(array_merge($childData, [
                    'uuid' => fake()->uuid(),
                    'parent_id' => $parent->id,
                    'description' => ['en' => "Products in the {$childData['name']['en']} subcategory"],
                    'is_active' => true,
                ]));
            }
        }

        return ProductCategory::all();
    }

    /**
     * Create products for a specific category.
     *
     * @param  \Illuminate\Support\Collection<int, Tag>  $tags
     * @param  \Illuminate\Support\Collection<int, MediaItem>  $mediaItems
     * @param  \Illuminate\Support\Collection<int, ProductStore>  $stores
     */
    private function createProductsForCategory(ProductCategory $category, $tags, $mediaItems, $stores): void
    {
        $products = $this->getProductsForCategory($category->slug);

        foreach ($products as $index => $productData) {
            $featuredTag = $tags->isNotEmpty() ? $tags->random() : null;
            $featuredMedia = $mediaItems->isNotEmpty() ? $mediaItems->random() : null;

            // Find the appropriate store
            $store = $this->findStoreByName($stores, $productData['store'] ?? 'Amazon');

            $product = Product::create([
                'uuid' => fake()->uuid(),
                'title' => $productData['title'],
                'slug' => $productData['slug'],
                'description' => $productData['description'],
                'short_description' => $productData['short_description'] ?? null,
                'brand' => $productData['brand'] ?? null,
                'product_category_id' => $category->id,
                'product_store_id' => $store?->id,
                'featured_tag_id' => $featuredTag?->id,
                'featured_media_id' => $featuredMedia?->id,
                'price' => $productData['price'],
                'compare_at_price' => $productData['compare_at_price'] ?? null,
                'currency' => 'USD',
                'availability' => $productData['availability'] ?? 'in_stock',
                'affiliate_url' => $productData['affiliate_url'],
                'is_active' => true,
                'is_featured' => $productData['is_featured'] ?? false,
                'order' => $index,
            ]);

            // Attach random tags
            if ($tags->isNotEmpty()) {
                $productTags = $tags->random(min(rand(2, 4), $tags->count()));
                $product->tags()->attach($productTags->pluck('id'));
            }

            // Attach random images (for the images gallery)
            if ($mediaItems->isNotEmpty() && $mediaItems->count() >= 2) {
                $galleryImages = $mediaItems->random(min(rand(1, 3), $mediaItems->count()));
                $imageData = [];
                foreach ($galleryImages as $order => $image) {
                    $imageData[$image->id] = ['order' => $order];
                }
                $product->images()->attach($imageData);
            }
        }
    }

    /**
     * Find the appropriate store by name.
     *
     * @param  \Illuminate\Support\Collection<int, ProductStore>  $stores
     */
    private function findStoreByName($stores, string $name): ?ProductStore
    {
        return $stores->firstWhere('name', $name) ?? $stores->first();
    }

    /**
     * Get product data for a specific category.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getProductsForCategory(string $categorySlug): array
    {
        return match ($categorySlug) {
            'skillets-frying-pans' => [
                [
                    'title' => ['en' => 'Lodge Cast Iron Skillet 12"'],
                    'slug' => 'lodge-cast-iron-skillet-12',
                    'description' => ['en' => 'Pre-seasoned cast iron skillet perfect for searing, baking, and everything in between. A kitchen workhorse that lasts generations.'],
                    'short_description' => ['en' => 'Pre-seasoned 12" cast iron skillet for versatile cooking'],
                    'brand' => 'Lodge',
                    'price' => 44.99,
                    'compare_at_price' => 54.99,
                    'availability' => 'in_stock',
                    'is_featured' => true,
                    'affiliate_url' => 'https://amazon.com/dp/example1',
                    'store' => 'Amazon',
                ],
                [
                    'title' => ['en' => 'All-Clad Stainless Steel Fry Pan 10"'],
                    'slug' => 'all-clad-stainless-fry-pan-10',
                    'description' => ['en' => 'Professional-grade tri-ply stainless steel pan with exceptional heat distribution. Perfect for deglazing and building fond.'],
                    'short_description' => ['en' => 'Tri-ply stainless steel for professional results'],
                    'brand' => 'All-Clad',
                    'price' => 149.99,
                    'compare_at_price' => 179.99,
                    'availability' => 'in_stock',
                    'affiliate_url' => 'https://amazon.com/dp/example3',
                    'store' => 'Amazon',
                ],
            ],
            'dutch-ovens' => [
                [
                    'title' => ['en' => 'Le Creuset Dutch Oven 5.5 Qt'],
                    'slug' => 'le-creuset-dutch-oven-5qt',
                    'description' => ['en' => 'Iconic enameled cast iron Dutch oven ideal for slow cooking, braising, and making soups. The gold standard of cookware.'],
                    'short_description' => ['en' => 'Iconic enameled cast iron for braising and slow cooking'],
                    'brand' => 'Le Creuset',
                    'price' => 379.99,
                    'availability' => 'in_stock',
                    'is_featured' => true,
                    'affiliate_url' => 'https://amazon.com/dp/example2',
                    'store' => 'Williams Sonoma',
                ],
                [
                    'title' => ['en' => 'Staub Cocotte Round 4 Qt'],
                    'slug' => 'staub-cocotte-round-4qt',
                    'description' => ['en' => 'French enameled cast iron cocotte with self-basting lid spikes. Creates perfectly moist braises and stews.'],
                    'short_description' => ['en' => 'Self-basting lid for perfectly moist dishes'],
                    'brand' => 'Staub',
                    'price' => 299.99,
                    'availability' => 'in_stock',
                    'affiliate_url' => 'https://amazon.com/dp/example4',
                    'store' => 'Sur La Table',
                ],
            ],
            'knives' => [
                [
                    'title' => ['en' => 'Victorinox Fibrox Pro Chef\'s Knife 8"'],
                    'slug' => 'victorinox-fibrox-chef-knife-8',
                    'description' => ['en' => 'The best value chef\'s knife on the market. Sharp, well-balanced, and perfect for home cooks and professionals alike.'],
                    'short_description' => ['en' => 'Best value chef knife for all skill levels'],
                    'brand' => 'Victorinox',
                    'price' => 34.99,
                    'availability' => 'in_stock',
                    'is_featured' => true,
                    'affiliate_url' => 'https://amazon.com/dp/example5',
                    'store' => 'Amazon',
                ],
            ],
            'utensils' => [
                [
                    'title' => ['en' => 'Microplane Premium Zester'],
                    'slug' => 'microplane-premium-zester',
                    'description' => ['en' => 'The original and best zester for citrus, hard cheeses, ginger, and more. Razor-sharp blades make quick work of zesting.'],
                    'short_description' => ['en' => 'Razor-sharp blades for effortless zesting'],
                    'brand' => 'Microplane',
                    'price' => 14.99,
                    'availability' => 'in_stock',
                    'affiliate_url' => 'https://amazon.com/dp/example6',
                    'store' => 'Amazon',
                ],
                [
                    'title' => ['en' => 'OXO Good Grips Wooden Spoon Set'],
                    'slug' => 'oxo-wooden-spoon-set',
                    'description' => ['en' => 'Set of 3 beechwood cooking spoons in essential shapes. Gentle on cookware and comfortable to hold.'],
                    'short_description' => ['en' => 'Set of 3 beechwood spoons'],
                    'brand' => 'OXO',
                    'price' => 19.99,
                    'availability' => 'in_stock',
                    'affiliate_url' => 'https://amazon.com/dp/example7',
                    'store' => 'Amazon',
                ],
                [
                    'title' => ['en' => 'Granite Mortar and Pestle'],
                    'slug' => 'granite-mortar-pestle',
                    'description' => ['en' => 'Heavy-duty granite mortar and pestle for grinding spices, making curry pastes, and crushing garlic. Essential for authentic flavors.'],
                    'short_description' => ['en' => 'Heavy-duty granite for grinding spices'],
                    'brand' => null,
                    'price' => 39.99,
                    'compare_at_price' => 49.99,
                    'availability' => 'in_stock',
                    'affiliate_url' => 'https://amazon.com/dp/example8',
                    'store' => 'Amazon',
                ],
            ],
            'cutting-boards' => [
                [
                    'title' => ['en' => 'Bamboo Cutting Board Set'],
                    'slug' => 'bamboo-cutting-board-set',
                    'description' => ['en' => 'Set of 3 sustainable bamboo cutting boards in various sizes. Knife-friendly and naturally antimicrobial.'],
                    'short_description' => ['en' => 'Sustainable bamboo in 3 sizes'],
                    'brand' => null,
                    'price' => 29.99,
                    'availability' => 'in_stock',
                    'affiliate_url' => 'https://amazon.com/dp/example9',
                    'store' => 'Amazon',
                ],
            ],
            'blenders' => [
                [
                    'title' => ['en' => 'Vitamix E310 Explorian Blender'],
                    'slug' => 'vitamix-e310-blender',
                    'description' => ['en' => 'Professional-grade blender for silky smooth soups, smoothies, and sauces. Variable speed control and self-cleaning.'],
                    'short_description' => ['en' => 'Professional-grade with variable speed'],
                    'brand' => 'Vitamix',
                    'price' => 349.99,
                    'compare_at_price' => 399.99,
                    'availability' => 'in_stock',
                    'is_featured' => true,
                    'affiliate_url' => 'https://amazon.com/dp/example12',
                    'store' => 'Williams Sonoma',
                ],
            ],
            'stand-mixers' => [
                [
                    'title' => ['en' => 'KitchenAid Artisan Stand Mixer 5 Qt'],
                    'slug' => 'kitchenaid-artisan-stand-mixer',
                    'description' => ['en' => 'The iconic stand mixer for serious bakers. 10 speeds, planetary mixing action, and dozens of attachment options.'],
                    'short_description' => ['en' => '10 speeds with planetary mixing action'],
                    'brand' => 'KitchenAid',
                    'price' => 449.99,
                    'availability' => 'in_stock',
                    'is_featured' => true,
                    'affiliate_url' => 'https://amazon.com/dp/example11',
                    'store' => 'Williams Sonoma',
                ],
            ],
            'air-fryers' => [
                [
                    'title' => ['en' => 'Ninja Foodi Air Fryer'],
                    'slug' => 'ninja-foodi-air-fryer',
                    'description' => ['en' => 'Dual-zone air fryer that can cook two foods at once. Crispy results with up to 75% less fat than traditional frying.'],
                    'short_description' => ['en' => 'Dual-zone cooking with 75% less fat'],
                    'brand' => 'Ninja',
                    'price' => 179.99,
                    'availability' => 'in_stock',
                    'affiliate_url' => 'https://amazon.com/dp/example13',
                    'store' => 'Amazon',
                ],
                [
                    'title' => ['en' => 'Instant Pot Duo 6 Qt'],
                    'slug' => 'instant-pot-duo-6qt',
                    'description' => ['en' => '7-in-1 electric pressure cooker that also slow cooks, sautés, steams, and more. Perfect for busy weeknight dinners.'],
                    'short_description' => ['en' => '7-in-1 multi-cooker for busy cooks'],
                    'brand' => 'Instant Pot',
                    'price' => 89.99,
                    'compare_at_price' => 99.99,
                    'availability' => 'in_stock',
                    'affiliate_url' => 'https://amazon.com/dp/example10',
                    'store' => 'Amazon',
                ],
            ],
            'oils-vinegars' => [
                [
                    'title' => ['en' => 'California Olive Ranch Extra Virgin Olive Oil'],
                    'slug' => 'california-olive-ranch-evoo',
                    'description' => ['en' => 'Fresh, peppery California olive oil perfect for finishing dishes, dressings, and everyday cooking.'],
                    'short_description' => ['en' => 'Fresh California olive oil for everyday use'],
                    'brand' => 'California Olive Ranch',
                    'price' => 16.99,
                    'availability' => 'in_stock',
                    'affiliate_url' => 'https://amazon.com/dp/example14',
                    'store' => 'Amazon',
                ],
            ],
            'spices' => [
                [
                    'title' => ['en' => 'Diaspora Co. Turmeric Powder'],
                    'slug' => 'diaspora-turmeric-powder',
                    'description' => ['en' => 'Single-origin, high-curcumin turmeric with vibrant color and complex flavor. Farm-direct and sustainably sourced.'],
                    'short_description' => ['en' => 'Single-origin high-curcumin turmeric'],
                    'brand' => 'Diaspora Co.',
                    'price' => 14.99,
                    'availability' => 'in_stock',
                    'is_featured' => true,
                    'affiliate_url' => 'https://diasporaco.com',
                    'store' => 'Diaspora Co.',
                ],
                [
                    'title' => ['en' => 'Maldon Sea Salt Flakes'],
                    'slug' => 'maldon-sea-salt-flakes',
                    'description' => ['en' => 'Delicate pyramid-shaped salt crystals for finishing dishes. The satisfying crunch that elevates any plate.'],
                    'short_description' => ['en' => 'Pyramid-shaped finishing salt'],
                    'brand' => 'Maldon',
                    'price' => 8.99,
                    'availability' => 'in_stock',
                    'affiliate_url' => 'https://amazon.com/dp/example17',
                    'store' => 'Amazon',
                ],
            ],
            'sauces' => [
                [
                    'title' => ['en' => 'Red Boat Fish Sauce'],
                    'slug' => 'red-boat-fish-sauce',
                    'description' => ['en' => 'First-press, single-origin fish sauce from Vietnam. Adds incredible umami depth to any dish.'],
                    'short_description' => ['en' => 'First-press Vietnamese fish sauce'],
                    'brand' => 'Red Boat',
                    'price' => 12.99,
                    'availability' => 'in_stock',
                    'affiliate_url' => 'https://amazon.com/dp/example15',
                    'store' => 'Amazon',
                ],
                [
                    'title' => ['en' => 'Aroy-D Coconut Cream'],
                    'slug' => 'aroy-d-coconut-cream',
                    'description' => ['en' => 'Rich, thick coconut cream without additives. Essential for authentic curries and Southeast Asian desserts.'],
                    'short_description' => ['en' => 'Pure coconut cream for curries'],
                    'brand' => 'Aroy-D',
                    'price' => 3.99,
                    'availability' => 'in_stock',
                    'affiliate_url' => 'https://amazon.com/dp/example16',
                    'store' => 'Amazon',
                ],
            ],
            'plates-bowls' => [
                [
                    'title' => ['en' => 'Heath Ceramics Coupe Dinner Plate'],
                    'slug' => 'heath-ceramics-coupe-plate',
                    'description' => ['en' => 'Handcrafted stoneware plate in classic California modern style. Each piece is unique with subtle variations.'],
                    'short_description' => ['en' => 'Handcrafted California stoneware'],
                    'brand' => 'Heath Ceramics',
                    'price' => 48.00,
                    'availability' => 'in_stock',
                    'is_featured' => true,
                    'affiliate_url' => 'https://heathceramics.com',
                    'store' => 'Heath Ceramics',
                ],
                [
                    'title' => ['en' => 'East Fork Everyday Bowl'],
                    'slug' => 'east-fork-everyday-bowl',
                    'description' => ['en' => 'Versatile, hand-thrown bowl perfect for grain bowls, soups, and pasta. Made in Asheville, NC.'],
                    'short_description' => ['en' => 'Hand-thrown versatile bowl'],
                    'brand' => 'East Fork',
                    'price' => 40.00,
                    'availability' => 'pre_order',
                    'affiliate_url' => 'https://eastfork.com',
                    'store' => 'Heath Ceramics',
                ],
            ],
            'serveware' => [
                [
                    'title' => ['en' => 'Material Kitchen Good Knives Set'],
                    'slug' => 'material-good-knives-set',
                    'description' => ['en' => 'Set of 3 essential knives with reBoard knife block. Thoughtfully designed for modern home cooks.'],
                    'short_description' => ['en' => '3 essential knives with knife block'],
                    'brand' => 'Material Kitchen',
                    'price' => 185.00,
                    'availability' => 'out_of_stock',
                    'affiliate_url' => 'https://materialkitchen.com',
                    'store' => 'Sur La Table',
                ],
            ],
            default => [],
        };
    }
}
