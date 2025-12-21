<?php

namespace App\View\Components\Layout;

use App\View\Concerns\ResolvesColors;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CardContainer extends Component
{
    use ResolvesColors;

    public string $mode;

    public string $mobileMode;

    public int $columns;

    public string $gap;

    public string $paddingX;

    public bool $showDividers;

    public string $dividerColor;

    public string $bgColorClass;

    public string $bgColorStyle;

    /**
     * Create a new component instance.
     *
     * @param  string  $bgColor  Background color (named, Tailwind class, hex, or rgba)
     */
    public function __construct(
        string $mode = 'grid',
        string $mobileMode = 'scroll',
        int $columns = 3,
        string $gap = '10',
        string $paddingX = '10',
        bool $showDividers = false,
        string $dividerColor = 'white',
        string $bgColor = '',
    ) {
        $this->mode = $mode;
        $this->mobileMode = $mobileMode;
        $this->columns = $columns;
        $this->gap = $gap;
        $this->paddingX = $paddingX;
        $this->showDividers = $showDividers;
        $this->dividerColor = str_starts_with($dividerColor, 'bg-') ? $dividerColor : ($dividerColor === 'white' ? 'bg-white' : 'bg-gray-300');

        if ($bgColor) {
            $resolved = $this->resolveBgColor($bgColor);
            $this->bgColorClass = $resolved['class'];
            $this->bgColorStyle = $resolved['style'];
        } else {
            $this->bgColorClass = '';
            $this->bgColorStyle = '';
        }
    }

    /**
     * Get the container classes based on mode.
     */
    public function containerClasses(): string
    {
        $classes = [];

        if ($this->bgColorClass) {
            $classes[] = $this->bgColorClass;
        }

        // Desktop mode
        if ($this->mode === 'scroll') {
            $classes[] = 'scroll-container scrollbar-hide';
        }

        // Mobile mode override
        if ($this->mode === 'grid' && $this->mobileMode === 'scroll') {
            $classes[] = 'max-md:scroll-container max-md:scrollbar-hide';
        }

        return implode(' ', $classes);
    }

    /**
     * Get the inner wrapper classes.
     */
    public function innerClasses(): string
    {
        $classes = ['flex'];

        // Gap
        $classes[] = "gap-{$this->gap}";

        // Padding
        $classes[] = "px-{$this->paddingX}";

        // Desktop mode
        if ($this->mode === 'grid') {
            $classes[] = 'flex-wrap';
        } else {
            $classes[] = 'min-w-max';
        }

        // Mobile mode
        if ($this->mode === 'grid' && $this->mobileMode === 'scroll') {
            $classes[] = 'max-md:flex-nowrap max-md:min-w-max';
        }

        return implode(' ', $classes);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.layout.card-container');
    }
}
