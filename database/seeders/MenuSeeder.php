<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedHeaderMenus();
        $this->seedFooterMenus();
    }

    private function seedHeaderMenus(): void
    {
        // Header Primary Menu
        $headerPrimary = Menu::updateOrCreate(
            ['location' => 'header-primary'],
            [
                'name' => ['en' => 'Header Primary'],
                'description' => ['en' => 'Main navigation links'],
                'is_active' => true,
            ]
        );

        $this->createMenuItems($headerPrimary, [
            ['label' => 'Update', 'url' => '#'],
            ['label' => 'Feature', 'url' => '#'],
            ['label' => 'People', 'url' => '#'],
            ['label' => 'Review', 'url' => '#'],
            ['label' => 'Recipe', 'url' => '#'],
            ['label' => 'Pantry', 'url' => '#'],
        ]);

        // Header Secondary Menu
        $headerSecondary = Menu::updateOrCreate(
            ['location' => 'header-secondary'],
            [
                'name' => ['en' => 'Header Secondary'],
                'description' => ['en' => 'Secondary navigation links'],
                'is_active' => true,
            ]
        );

        $this->createMenuItems($headerSecondary, [
            ['label' => 'About', 'url' => '/about', 'page_slug' => 'about'],
            ['label' => 'Advertise', 'url' => '/advertise', 'page_slug' => 'advertise'],
        ]);

        // Mobile Actions
        $mobileActions = Menu::updateOrCreate(
            ['location' => 'mobile-actions'],
            [
                'name' => ['en' => 'Mobile Actions'],
                'description' => ['en' => 'Mobile menu action buttons'],
                'is_active' => true,
            ]
        );

        $this->createMenuItems($mobileActions, [
            ['label' => 'Subscribe', 'url' => '#'],
            ['label' => 'Search', 'url' => '#'],
        ]);
    }

    private function seedFooterMenus(): void
    {
        // Footer Menu
        $footerMenu = Menu::updateOrCreate(
            ['location' => 'footer-menu'],
            [
                'name' => ['en' => 'Footer Menu'],
                'description' => ['en' => 'Main footer navigation'],
                'is_active' => true,
            ]
        );

        $this->createMenuItems($footerMenu, [
            ['label' => 'The Spread', 'url' => '#'],
            ['label' => 'On the Menu', 'url' => '#'],
            ['label' => 'Everyday Cooking', 'url' => '#'],
            ['label' => 'Food Destinations', 'url' => '#'],
            ['label' => 'Fresh This Week', 'url' => '#'],
            ['label' => 'Add to Cart', 'url' => '#'],
        ]);

        // Footer Topics
        $footerTopics = Menu::updateOrCreate(
            ['location' => 'footer-topics'],
            [
                'name' => ['en' => 'Footer Topics'],
                'description' => ['en' => 'Topic links in footer'],
                'is_active' => true,
            ]
        );

        $this->createMenuItems($footerTopics, [
            ['label' => 'At the Table', 'url' => '#'],
            ['label' => 'Chef Stories', 'url' => '#'],
            ['label' => 'Island Profiles', 'url' => '#'],
            ['label' => 'Ingredient Files', 'url' => '#'],
            ['label' => 'Travel & Taste', 'url' => '#'],
            ['label' => 'Weekly Updates', 'url' => '#'],
        ]);

        // Footer Office
        $footerOffice = Menu::updateOrCreate(
            ['location' => 'footer-office'],
            [
                'name' => ['en' => 'Footer Office'],
                'description' => ['en' => 'Office/company links in footer'],
                'is_active' => true,
            ]
        );

        $this->createMenuItems($footerOffice, [
            ['label' => 'About', 'url' => '/about', 'page_slug' => 'about'],
            ['label' => 'Contact', 'url' => '/contact', 'page_slug' => 'contact'],
            ['label' => 'Editorial Policy', 'url' => '/editorial-policy', 'page_slug' => 'editorial-policy'],
            ['label' => 'Work With Us', 'url' => '/work-with-us', 'page_slug' => 'work-with-us'],
            ['label' => 'Submit a Story', 'url' => '/submit-story', 'page_slug' => 'submit-story'],
            ['label' => 'Archive', 'url' => '#'],
        ]);

        // Footer Social
        $footerSocial = Menu::updateOrCreate(
            ['location' => 'footer-social'],
            [
                'name' => ['en' => 'Footer Social'],
                'description' => ['en' => 'Social media links in footer'],
                'is_active' => true,
            ]
        );

        $this->createMenuItems($footerSocial, [
            ['label' => 'Instagram', 'url' => '#', 'icon' => 'fab fa-instagram'],
            ['label' => 'TikTok', 'url' => '#', 'icon' => 'fab fa-tiktok'],
            ['label' => 'YouTube', 'url' => '#', 'icon' => 'fab fa-youtube'],
            ['label' => 'Threads', 'url' => '#', 'icon' => 'fab fa-threads'],
            ['label' => 'X.com', 'url' => '#', 'icon' => 'fab fa-x-twitter'],
            ['label' => 'Newsletter', 'url' => '#', 'icon' => 'fas fa-envelope'],
        ]);
    }

    /**
     * Create menu items for a menu.
     *
     * @param  array<int, array{label: string, url: string, page_slug?: string, icon?: string}>  $items
     */
    private function createMenuItems(Menu $menu, array $items): void
    {
        // Clear existing items for this menu
        $menu->allItems()->delete();

        foreach ($items as $order => $item) {
            $menuItemData = [
                'menu_id' => $menu->id,
                'label' => ['en' => $item['label']],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => $item['url'],
                'order' => $order,
                'is_active' => true,
            ];

            // If page_slug is provided, link to the page
            if (isset($item['page_slug'])) {
                $page = Page::where('slug', $item['page_slug'])->first();
                if ($page) {
                    $menuItemData['type'] = MenuItem::TYPE_PAGE;
                    $menuItemData['linkable_type'] = Page::class;
                    $menuItemData['linkable_id'] = $page->id;
                }
            }

            if (isset($item['icon'])) {
                $menuItemData['icon'] = $item['icon'];
            }

            MenuItem::create($menuItemData);
        }
    }
}
