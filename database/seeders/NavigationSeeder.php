<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class NavigationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Header Primary Navigation
        $headerPrimary = Menu::updateOrCreate(
            ['location' => 'header-primary'],
            [
                'name' => ['en' => 'Header Primary', 'dv' => 'ހެޑަރ ޕްރައިމަރީ'],
                'description' => ['en' => 'Main navigation links in the header', 'dv' => 'ހެޑަރގައި ހުންނަ މައިގަނޑު ލިންކުތައް'],
                'is_active' => true,
            ]
        );

        $this->createMenuItems($headerPrimary, [
            [
                'label' => ['en' => 'Recipes', 'dv' => 'ރެސިޕީތައް'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/recipes',
            ],
            [
                'label' => ['en' => 'Restaurants', 'dv' => 'ރެސްޓޯރެންޓްތައް'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/restaurants',
            ],
            [
                'label' => ['en' => 'Videos', 'dv' => 'ވީޑިއޯ'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/videos',
            ],
            [
                'label' => ['en' => 'Blog', 'dv' => 'ބްލޮގް'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/blog',
            ],
        ]);

        // Header Secondary Navigation
        $headerSecondary = Menu::updateOrCreate(
            ['location' => 'header-secondary'],
            [
                'name' => ['en' => 'Header Secondary', 'dv' => 'ހެޑަރ ސެކެންޑަރީ'],
                'description' => ['en' => 'Secondary navigation links in the header', 'dv' => 'ހެޑަރގައި ހުންނަ ދެވަނަ ލިންކުތައް'],
                'is_active' => true,
            ]
        );

        $this->createMenuItems($headerSecondary, [
            [
                'label' => ['en' => 'About', 'dv' => 'އެބައުޓް'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/about',
            ],
            [
                'label' => ['en' => 'Contact', 'dv' => 'ގުޅާލާ'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/contact',
            ],
        ]);

        // Mobile Actions (CTA buttons)
        $mobileActions = Menu::updateOrCreate(
            ['location' => 'mobile-actions'],
            [
                'name' => ['en' => 'Mobile Actions', 'dv' => 'މޮބައިލް އެކްޝަންތައް'],
                'description' => ['en' => 'CTA buttons shown on mobile menu', 'dv' => 'މޮބައިލް މެނޫގައި ދައްކާ ބަޓަންތައް'],
                'is_active' => true,
            ]
        );

        $this->createMenuItems($mobileActions, [
            [
                'label' => ['en' => 'Search', 'dv' => 'ހޯދާ'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '#',
                'css_classes' => ['variant-primary'],
            ],
            [
                'label' => ['en' => 'Subscribe', 'dv' => 'ސަބްސްކްރައިބް'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/subscribe',
                'css_classes' => ['variant-outline'],
            ],
        ]);

        // Footer Menu
        $footerMenu = Menu::updateOrCreate(
            ['location' => 'footer-menu'],
            [
                'name' => ['en' => 'Footer Menu', 'dv' => 'ފޫޓަރ މެނޫ'],
                'description' => ['en' => 'Main menu links in the footer', 'dv' => 'ފޫޓަރގައި ހުންނަ މައިގަނޑު ލިންކުތައް'],
                'is_active' => true,
            ]
        );

        $this->createMenuItems($footerMenu, [
            [
                'label' => ['en' => 'Home', 'dv' => 'ހޯމް'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/',
            ],
            [
                'label' => ['en' => 'Recipes', 'dv' => 'ރެސިޕީތައް'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/recipes',
            ],
            [
                'label' => ['en' => 'Restaurants', 'dv' => 'ރެސްޓޯރެންޓްތައް'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/restaurants',
            ],
            [
                'label' => ['en' => 'Videos', 'dv' => 'ވީޑިއޯ'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/videos',
            ],
            [
                'label' => ['en' => 'Blog', 'dv' => 'ބްލޮގް'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/blog',
            ],
            [
                'label' => ['en' => 'About', 'dv' => 'އެބައުޓް'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/about',
            ],
        ]);

        // Footer Topics (Categories)
        $footerTopics = Menu::updateOrCreate(
            ['location' => 'footer-topics'],
            [
                'name' => ['en' => 'Footer Topics', 'dv' => 'ފޫޓަރ ޓޮޕިކްސް'],
                'description' => ['en' => 'Topic/category links in the footer', 'dv' => 'ފޫޓަރގައި ހުންނަ ޓޮޕިކް ލިންކުތައް'],
                'is_active' => true,
            ]
        );

        $this->createMenuItems($footerTopics, [
            [
                'label' => ['en' => 'Breakfast', 'dv' => 'ހެނދުނުގެ ކެއުން'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/category/breakfast',
            ],
            [
                'label' => ['en' => 'Lunch', 'dv' => 'މެންދުރުގެ ކެއުން'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/category/lunch',
            ],
            [
                'label' => ['en' => 'Dinner', 'dv' => 'ރޭގަނޑުގެ ކެއުން'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/category/dinner',
            ],
            [
                'label' => ['en' => 'Desserts', 'dv' => 'ފޮނި ކާތަކެތި'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/category/desserts',
            ],
            [
                'label' => ['en' => 'Quick & Easy', 'dv' => 'އަވަސް އަދި ފަސޭހަ'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/category/quick-easy',
            ],
            [
                'label' => ['en' => 'Healthy', 'dv' => 'ސިއްހީ'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/category/healthy',
            ],
        ]);

        // Footer Office
        $footerOffice = Menu::updateOrCreate(
            ['location' => 'footer-office'],
            [
                'name' => ['en' => 'Footer Office', 'dv' => 'ފޫޓަރ އޮފީސް'],
                'description' => ['en' => 'Office/contact information in the footer', 'dv' => 'ފޫޓަރގައި ހުންނަ އޮފީސް މައުލޫމާތު'],
                'is_active' => true,
            ]
        );

        $this->createMenuItems($footerOffice, [
            [
                'label' => ['en' => 'Contact Us', 'dv' => 'ގުޅާލާ'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/contact',
            ],
            [
                'label' => ['en' => 'Advertise', 'dv' => 'އިޝްތިހާރު'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/advertise',
            ],
            [
                'label' => ['en' => 'Careers', 'dv' => 'ވަޒީފާ'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/careers',
            ],
            [
                'label' => ['en' => 'Privacy Policy', 'dv' => 'ޕްރައިވެސީ ޕޮލިސީ'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/privacy',
            ],
            [
                'label' => ['en' => 'Terms of Service', 'dv' => 'ޓާރމްސް އޮފް ސާރވިސް'],
                'type' => MenuItem::TYPE_CUSTOM,
                'url' => '/terms',
            ],
        ]);

        // Footer Social
        $footerSocial = Menu::updateOrCreate(
            ['location' => 'footer-social'],
            [
                'name' => ['en' => 'Footer Social', 'dv' => 'ފޫޓަރ ސޯޝަލް'],
                'description' => ['en' => 'Social media links in the footer', 'dv' => 'ފޫޓަރގައި ހުންނަ ސޯޝަލް މީޑިއާ ލިންކުތައް'],
                'is_active' => true,
            ]
        );

        $this->createMenuItems($footerSocial, [
            [
                'label' => ['en' => 'Facebook', 'dv' => 'ފޭސްބުކް'],
                'type' => MenuItem::TYPE_EXTERNAL,
                'url' => 'https://facebook.com/tasty',
                'target' => '_blank',
                'icon' => 'fab fa-facebook',
            ],
            [
                'label' => ['en' => 'Instagram', 'dv' => 'އިންސްޓަގްރާމް'],
                'type' => MenuItem::TYPE_EXTERNAL,
                'url' => 'https://instagram.com/tasty',
                'target' => '_blank',
                'icon' => 'fab fa-instagram',
            ],
            [
                'label' => ['en' => 'Twitter', 'dv' => 'ޓުވިޓަރ'],
                'type' => MenuItem::TYPE_EXTERNAL,
                'url' => 'https://twitter.com/tasty',
                'target' => '_blank',
                'icon' => 'fab fa-twitter',
            ],
            [
                'label' => ['en' => 'YouTube', 'dv' => 'ޔޫޓިއުބް'],
                'type' => MenuItem::TYPE_EXTERNAL,
                'url' => 'https://youtube.com/tasty',
                'target' => '_blank',
                'icon' => 'fab fa-youtube',
            ],
            [
                'label' => ['en' => 'TikTok', 'dv' => 'ޓިކްޓޮކް'],
                'type' => MenuItem::TYPE_EXTERNAL,
                'url' => 'https://tiktok.com/@tasty',
                'target' => '_blank',
                'icon' => 'fab fa-tiktok',
            ],
        ]);
    }

    /**
     * Create menu items for a given menu.
     *
     * @param  array<int, array{label: array<string, string>, type: string, url: string, target?: string, icon?: string, css_classes?: array<int, string>}>  $items
     */
    private function createMenuItems(Menu $menu, array $items): void
    {
        // Clear existing items for this menu
        $menu->allItems()->delete();

        foreach ($items as $order => $item) {
            MenuItem::create([
                'menu_id' => $menu->id,
                'label' => $item['label'],
                'type' => $item['type'],
                'url' => $item['url'] ?? null,
                'target' => $item['target'] ?? '_self',
                'icon' => $item['icon'] ?? null,
                'css_classes' => $item['css_classes'] ?? null,
                'order' => $order,
                'is_active' => true,
            ]);
        }
    }
}
