<?php

namespace App\View\Components\Layout;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NavBar extends Component
{
    public array $primaryLinks;

    public array $secondaryLinks;

    public array $mobileActions;

    public function __construct(
        ?array $primaryLinks = null,
        ?array $secondaryLinks = null,
        ?array $mobileActions = null,
    ) {
        $this->primaryLinks = $primaryLinks ?? [
            ['label' => 'Update', 'href' => '#', 'class' => 'text-blue-black'],
            ['label' => 'Feature', 'href' => '#', 'class' => 'text-black'],
            ['label' => 'People', 'href' => '#', 'class' => 'text-black'],
            ['label' => 'Review', 'href' => '#', 'class' => 'text-blue-black'],
            ['label' => 'Recipe', 'href' => '#', 'class' => 'text-blue-black'],
            ['label' => 'Pantry', 'href' => '#', 'class' => 'text-blue-black'],
        ];

        $this->secondaryLinks = $secondaryLinks ?? [
            ['label' => 'About', 'href' => '#', 'class' => 'text-blue-black'],
            ['label' => 'Advertise', 'href' => '#', 'class' => 'text-black'],
        ];

        $this->mobileActions = $mobileActions ?? [
            ['label' => 'Subscribe', 'href' => '#', 'variant' => 'primary'],
            ['label' => 'Search', 'href' => '#', 'variant' => 'outline'],
        ];
    }

    public function render(): View|Closure|string
    {
        return view('components.layout.nav-bar');
    }
}
