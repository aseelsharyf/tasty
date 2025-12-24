<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MediaItem;
use App\Models\Setting;
use App\Services\Layouts\SectionRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;

class HomepageLayoutSeeder extends Seeder
{
    public function __construct(
        protected SectionRegistry $registry
    ) {}

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get random categories for data sources
        $categories = Category::query()
            ->whereNull('parent_id')
            ->inRandomOrder()
            ->limit(5)
            ->get();

        $categoryIndex = 0;
        $getNextCategory = function () use ($categories, &$categoryIndex) {
            if ($categories->isEmpty()) {
                return null;
            }
            $category = $categories[$categoryIndex % $categories->count()];
            $categoryIndex++;

            return $category;
        };

        $sections = [
            // Hero - Dynamic, recent posts
            $this->createSection('hero', 0, [
                'alignment' => 'center',
                'bgColor' => 'yellow',
                'buttonText' => 'Read More',
                'buttonColor' => 'white',
            ], [
                'action' => 'recent',
                'params' => [],
            ]),

            // Latest Updates - Dynamic, by category
            $this->createSection('latest-updates', 1, [
                'introImage' => Vite::asset('resources/images/latest-updates-transparent.png'),
                'introImageAlt' => 'Latest Updates',
                'titleSmall' => 'Latest',
                'titleLarge' => 'Updates',
                'description' => 'The flavors, characters, and tiny island obsessions that makes the Maldivian food culture.',
                'buttonText' => 'More Updates',
                'showLoadMore' => true,
                'featuredCount' => 1,
                'postsCount' => 4,
            ], $this->getCategoryDataSource($getNextCategory())),

            // Featured Person - Dynamic, recent
            $this->createSection('featured-person', 2, [
                'bgColor' => 'yellow',
                'buttonText' => 'Read More',
                'tag1' => 'TASTY FEATURE',
                'tag2' => '',
            ], [
                'action' => 'recent',
                'params' => [],
            ]),

            // Spread - Dynamic, by category
            $this->createSection('spread', 3, [
                'showIntro' => true,
                'introImage' => Vite::asset('resources/images/image-07.png'),
                'introImageAlt' => 'The Spread',
                'titleSmall' => 'The',
                'titleLarge' => 'SPREAD',
                'description' => 'Explore the latest from our kitchen to yours.',
                'bgColor' => 'yellow',
                'mobileLayout' => 'scroll',
                'showDividers' => true,
                'dividerColor' => 'white',
                'count' => 4,
            ], $this->getCategoryDataSource($getNextCategory())),

            // Featured Video - Dynamic, recent
            $this->createSection('featured-video', 4, [
                'buttonText' => 'Watch',
                'overlayColor' => '#FFE762',
                'showSectionGradient' => true,
                'sectionGradientDirection' => 'top',
                'sectionBgColor' => '',
            ], [
                'action' => 'recent',
                'params' => [],
            ]),

            // Review - Dynamic, by category
            $this->createSection('review', 5, [
                'showIntro' => true,
                'introImage' => Vite::asset('resources/images/on-the-menu.png'),
                'introImageAlt' => 'On the Menu',
                'titleSmall' => 'On the',
                'titleLarge' => 'Menu',
                'description' => "Restaurant reviews, chef crushes, and the dishes we can't stop talking about.",
                'mobileLayout' => 'scroll',
                'showDividers' => true,
                'dividerColor' => 'white',
                'buttonText' => 'More Reviews',
                'showLoadMore' => true,
                'count' => 5,
            ], $this->getCategoryDataSource($getNextCategory())),

            // Featured Location - Dynamic, recent
            $this->createSection('featured-location', 6, [
                'bgColor' => 'yellow',
                'textColor' => 'blue-black',
                'buttonVariant' => 'white',
                'buttonText' => 'Read More',
                'tag1' => 'TASTY FEATURE',
                'tag2' => '',
            ], [
                'action' => 'recent',
                'params' => [],
            ]),

            // Spread 2 - Dynamic, by category
            $this->createSection('spread', 7, [
                'showIntro' => false,
                'introImage' => '',
                'introImageAlt' => '',
                'titleSmall' => '',
                'titleLarge' => '',
                'description' => '',
                'bgColor' => 'yellow',
                'mobileLayout' => 'scroll',
                'showDividers' => true,
                'dividerColor' => 'white',
                'count' => 5,
            ], $this->getCategoryDataSource($getNextCategory())),

            // Recipe - Dynamic, by category
            $this->createSection('recipe', 8, [
                'showIntro' => true,
                'introImage' => Vite::asset('resources/images/on-the-menu.png'),
                'introImageAlt' => 'Everyday Cooking',
                'titleSmall' => 'Everyday',
                'titleLarge' => 'COOKING',
                'description' => 'The flavors, characters, and tiny island obsessions that makes the Maldivian food culture.',
                'bgColor' => 'yellow',
                'gradient' => 'top',
                'mobileLayout' => 'grid',
                'showDividers' => false,
                'dividerColor' => 'white',
                'count' => 3,
            ], $this->getCategoryDataSource($getNextCategory())),

            // Add to Cart - Static products (no dynamic data source)
            $this->createStaticAddToCartSection(9),

            // Newsletter - No slots
            $this->createSection('newsletter', 10, [
                'title' => 'COME HUNGRY, LEAVE INSPIRED. SIGN UP FOR TASTY UPDATES.',
                'placeholder' => 'Enter your Email',
                'buttonText' => 'SUBSCRIBE',
                'bgColor' => '#F3F4F6',
            ], [
                'action' => '',
                'params' => [],
            ]),
        ];

        $config = [
            'sections' => $sections,
            'version' => 1,
            'updatedAt' => now()->toIso8601String(),
            'updatedBy' => 1,
        ];

        Setting::set('layouts.homepage', $config, 'layouts');

        $this->command->info('Homepage layout seeded successfully with '.count($sections).' sections.');
    }

    /**
     * Create a section configuration.
     */
    protected function createSection(string $type, int $order, array $config = [], array $dataSource = []): array
    {
        $definition = $this->registry->get($type);

        $defaultConfig = $definition?->defaultConfig() ?? [];
        $mergedConfig = array_merge($defaultConfig, $config);

        $defaultDataSource = $definition?->defaultDataSource() ?? ['action' => 'recent', 'params' => []];
        $mergedDataSource = array_merge($defaultDataSource, $dataSource);

        // Create slots - all dynamic by default
        $slots = [];
        $slotCount = $definition?->slotCount() ?? 1;
        $slotSchema = $definition?->slotSchema() ?? [];
        $supportsDynamic = $definition?->supportsDynamic() ?? false;

        $defaultContent = [];
        foreach ($slotSchema as $key => $field) {
            $defaultContent[$key] = $field['default'] ?? '';
        }

        for ($i = 0; $i < $slotCount; $i++) {
            $slots[] = [
                'index' => $i,
                'mode' => $supportsDynamic ? 'dynamic' : 'static',
                'postId' => null,
                'content' => $defaultContent,
            ];
        }

        return [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'order' => $order,
            'enabled' => true,
            'config' => $mergedConfig,
            'dataSource' => $mergedDataSource,
            'slots' => $slots,
        ];
    }

    /**
     * Create static Add to Cart section with sample products.
     */
    protected function createStaticAddToCartSection(int $order): array
    {
        $definition = $this->registry->get('add-to-cart');

        // Get random media items for product images
        $mediaItems = MediaItem::query()
            ->inRandomOrder()
            ->limit(3)
            ->get();

        $sampleProducts = [
            [
                'title' => 'Ceramic Mixing Bowls Set',
                'description' => 'Beautiful handcrafted ceramic bowls perfect for mixing and serving.',
                'image' => $mediaItems->get(0)?->url ?? '',
                'imageAlt' => 'Ceramic Mixing Bowls Set',
                'tags' => ['KITCHEN', 'ESSENTIALS'],
                'url' => '#',
            ],
            [
                'title' => 'Premium Olive Oil',
                'description' => 'Cold-pressed extra virgin olive oil from local farms.',
                'image' => $mediaItems->get(1)?->url ?? '',
                'imageAlt' => 'Premium Olive Oil',
                'tags' => ['PANTRY', 'STAPLES'],
                'url' => '#',
            ],
            [
                'title' => 'Cast Iron Skillet',
                'description' => 'Pre-seasoned cast iron pan for perfect searing.',
                'image' => $mediaItems->get(2)?->url ?? '',
                'imageAlt' => 'Cast Iron Skillet',
                'tags' => ['COOKWARE', 'ESSENTIALS'],
                'url' => '#',
            ],
        ];

        $slots = [];
        foreach ($sampleProducts as $i => $product) {
            $slots[] = [
                'index' => $i,
                'mode' => 'static',
                'postId' => null,
                'content' => $product,
            ];
        }

        return [
            'id' => (string) Str::uuid(),
            'type' => 'add-to-cart',
            'order' => $order,
            'enabled' => true,
            'config' => [
                'title' => 'ADD TO CART',
                'description' => 'Ingredients, tools, and staples we actually use.',
                'bgColor' => 'white',
            ],
            'dataSource' => [
                'action' => '',
                'params' => [],
            ],
            'slots' => $slots,
        ];
    }

    /**
     * Get data source config for a category.
     */
    protected function getCategoryDataSource(?Category $category): array
    {
        if (! $category) {
            return [
                'action' => 'recent',
                'params' => [],
            ];
        }

        return [
            'action' => 'byCategory',
            'params' => [
                'slug' => $category->slug,
            ],
        ];
    }
}
