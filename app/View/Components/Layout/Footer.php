<?php

namespace App\View\Components\Layout;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Footer extends Component
{
    public array $menu;

    public array $topics;

    public array $office;

    public array $social;

    public string $company;

    public string $location;

    public int $year;

    public function __construct(
        ?array $menu = null,
        ?array $topics = null,
        ?array $office = null,
        ?array $social = null,
        ?string $company = null,
        ?string $location = null,
        ?int $year = null,
    ) {
        $this->menu = $menu ?? [
            ['label' => 'The Spread', 'url' => '#'],
            ['label' => 'On the Menu', 'url' => '#'],
            ['label' => 'Everyday Cooking', 'url' => '#'],
            ['label' => 'Food Destinations', 'url' => '#'],
            ['label' => 'Fresh This Week', 'url' => '#'],
            ['label' => 'Add to Cart', 'url' => '#'],
        ];

        $this->topics = $topics ?? [
            ['label' => 'At the Table', 'url' => '#'],
            ['label' => 'Chef Stories', 'url' => '#'],
            ['label' => 'Island Profiles', 'url' => '#'],
            ['label' => 'Ingredient Files', 'url' => '#'],
            ['label' => 'Travel & Taste', 'url' => '#'],
            ['label' => 'Weekly Updates', 'url' => '#'],
        ];

        $this->office = $office ?? [
            ['label' => 'About', 'url' => '#'],
            ['label' => 'Contact', 'url' => '#'],
            ['label' => 'Editorial Policy', 'url' => '#'],
            ['label' => 'Work With Us', 'url' => '#'],
            ['label' => 'Submit a Story', 'url' => '#'],
            ['label' => 'Archive', 'url' => '#'],
        ];

        $this->social = $social ?? [
            ['label' => 'Instagram', 'url' => '#'],
            ['label' => 'TikTok', 'url' => '#'],
            ['label' => 'YouTube', 'url' => '#'],
            ['label' => 'Threads', 'url' => '#'],
            ['label' => 'X.com', 'url' => '#'],
            ['label' => 'Newsletter', 'url' => '#'],
        ];

        $this->company = $company ?? 'Tasty Publishing Ltd.';
        $this->location = $location ?? 'Made in the Maldives.';
        $this->year = $year ?? (int) date('Y');
    }

    public function render(): View|Closure|string
    {
        return view('components.layout.footer');
    }
}
