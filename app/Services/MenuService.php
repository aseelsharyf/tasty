<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MenuService
{
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get menu items for a specific location with caching.
     *
     * @return array<int, array{label: string, href: string, class?: string, target?: string}>
     */
    public function getMenuByLocation(string $location, array $default = []): array
    {
        $cacheKey = "menu.location.{$location}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($location, $default) {
            $menu = Menu::findByLocation($location);

            if (! $menu) {
                return $default;
            }

            return $this->transformMenuItems($menu->getTree());
        });
    }

    /**
     * Transform menu items to array format.
     *
     * @param  Collection<int, \App\Models\MenuItem>  $items
     * @return array<int, array{label: string, href: string, class?: string, target?: string, children?: array}>
     */
    private function transformMenuItems(Collection $items): array
    {
        return $items->map(function ($item) {
            $transformed = [
                'label' => $item->label,
                'href' => $item->resolved_url ?? '#',
                'url' => $item->resolved_url ?? '#',
                'target' => $item->target ?? '_self',
            ];

            if ($item->css_classes) {
                $transformed['class'] = implode(' ', $item->css_classes);
            }

            if ($item->icon) {
                $transformed['icon'] = $item->icon;
            }

            if ($item->children->isNotEmpty()) {
                $transformed['children'] = $this->transformMenuItems($item->children);
            }

            return $transformed;
        })->all();
    }

    /**
     * Get header primary links.
     *
     * @return array<int, array{label: string, href: string, class?: string}>
     */
    public function getHeaderPrimaryLinks(): array
    {
        return $this->getMenuByLocation('header-primary', [
            ['label' => 'Update', 'href' => '/category/update', 'class' => 'text-blue-black'],
            ['label' => 'Feature', 'href' => '/category/feature', 'class' => 'text-black'],
            ['label' => 'People', 'href' => '/category/people', 'class' => 'text-black'],
            ['label' => 'Review', 'href' => '/category/review', 'class' => 'text-blue-black'],
            ['label' => 'Recipe', 'href' => '/category/recipe', 'class' => 'text-blue-black'],
            ['label' => 'Pantry', 'href' => '/category/pantry', 'class' => 'text-blue-black'],
        ]);
    }

    /**
     * Get header secondary links.
     *
     * @return array<int, array{label: string, href: string, class?: string}>
     */
    public function getHeaderSecondaryLinks(): array
    {
        return $this->getMenuByLocation('header-secondary', [
            ['label' => 'About', 'href' => '/about', 'class' => 'text-blue-black'],
            ['label' => 'Advertise', 'href' => '/advertise', 'class' => 'text-black'],
        ]);
    }

    /**
     * Get mobile actions.
     *
     * @return array<int, array{label: string, href: string, variant: string}>
     */
    public function getMobileActions(): array
    {
        return $this->getMenuByLocation('mobile-actions', [
            ['label' => 'Subscribe', 'href' => '#', 'variant' => 'primary'],
            ['label' => 'Search', 'href' => '#', 'variant' => 'outline'],
        ]);
    }

    /**
     * Get footer menu links.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public function getFooterMenu(): array
    {
        return $this->getMenuByLocation('footer-menu', [
            ['label' => 'The Spread', 'url' => '#'],
            ['label' => 'On the Menu', 'url' => '#'],
            ['label' => 'Everyday Cooking', 'url' => '#'],
            ['label' => 'Food Destinations', 'url' => '#'],
            ['label' => 'Fresh This Week', 'url' => '#'],
            ['label' => 'Add to Cart', 'url' => '#'],
        ]);
    }

    /**
     * Get footer topics links.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public function getFooterTopics(): array
    {
        return $this->getMenuByLocation('footer-topics', [
            ['label' => 'At the Table', 'url' => '#'],
            ['label' => 'Chef Stories', 'url' => '#'],
            ['label' => 'Island Profiles', 'url' => '#'],
            ['label' => 'Ingredient Files', 'url' => '#'],
            ['label' => 'Travel & Taste', 'url' => '#'],
            ['label' => 'Weekly Updates', 'url' => '#'],
        ]);
    }

    /**
     * Get footer office links.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public function getFooterOffice(): array
    {
        return $this->getMenuByLocation('footer-office', [
            ['label' => 'About', 'url' => '/about'],
            ['label' => 'Contact', 'url' => '/contact'],
            ['label' => 'Editorial Policy', 'url' => '/editorial-policy'],
            ['label' => 'Work With Us', 'url' => '/work-with-us'],
            ['label' => 'Submit a Story', 'url' => '/submit-story'],
            ['label' => 'Archive', 'url' => '#'],
        ]);
    }

    /**
     * Get footer social links.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public function getFooterSocial(): array
    {
        return $this->getMenuByLocation('footer-social', [
            ['label' => 'Instagram', 'url' => '#'],
            ['label' => 'TikTok', 'url' => '#'],
            ['label' => 'YouTube', 'url' => '#'],
            ['label' => 'Threads', 'url' => '#'],
            ['label' => 'X.com', 'url' => '#'],
            ['label' => 'Newsletter', 'url' => '#'],
        ]);
    }

    /**
     * Get footer company name.
     */
    public function getCompanyName(): string
    {
        return Cache::remember('site.company_name', self::CACHE_TTL, function () {
            return Setting::get('site.company_name', 'Tasty Publishing Ltd.');
        });
    }

    /**
     * Get footer location.
     */
    public function getCompanyLocation(): string
    {
        return Cache::remember('site.company_location', self::CACHE_TTL, function () {
            return Setting::get('site.company_location', 'Made in the Maldives.');
        });
    }

    /**
     * Clear all menu caches.
     */
    public function clearAllCaches(): void
    {
        $locations = [
            'header-primary',
            'header-secondary',
            'mobile-actions',
            'footer-menu',
            'footer-topics',
            'footer-office',
            'footer-social',
        ];

        foreach ($locations as $location) {
            Cache::forget("menu.location.{$location}");
        }

        Cache::forget('site.company_name');
        Cache::forget('site.company_location');
    }

    /**
     * Clear cache for a specific menu location.
     */
    public function clearLocationCache(string $location): void
    {
        Cache::forget("menu.location.{$location}");
    }
}
