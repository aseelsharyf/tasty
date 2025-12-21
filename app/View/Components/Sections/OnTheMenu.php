<?php

namespace App\View\Components\Sections;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class OnTheMenu extends Component
{
    public string $introImage;

    public string $introImageAlt;

    public string $titleSmall;

    public string $titleLarge;

    public string $description;

    /** @var array<int, array<string, mixed>> */
    public array $restaurants;

    /**
     * Create a new component instance.
     *
     * @param  array<int, array<string, mixed>>  $restaurants
     */
    public function __construct(
        string $introImage = '',
        string $introImageAlt = '',
        string $titleSmall = 'On the',
        string $titleLarge = 'Menu',
        string $description = '',
        array $restaurants = [],
    ) {
        $this->introImage = $introImage;
        $this->introImageAlt = $introImageAlt;
        $this->titleSmall = $titleSmall;
        $this->titleLarge = $titleLarge;
        $this->description = $description;
        $this->restaurants = $restaurants;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.sections.on-the-menu');
    }
}
