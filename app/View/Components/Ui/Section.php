<?php

namespace App\View\Components\Ui;

use App\View\Concerns\ResolvesColors;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Section extends Component
{
    use ResolvesColors;

    public string $bgClass;

    public string $bgStyle;

    public string $padding;

    public string $maxWidth;

    public bool $container;

    /**
     * Additional named colors for Section component.
     *
     * @var array<string, string>
     */
    protected array $additionalBgColors = [
        'blue-black' => 'bg-blue-black',
        'transparent' => 'bg-transparent',
    ];

    /**
     * Available padding presets.
     *
     * @var array<string, string>
     */
    protected array $paddings = [
        'none' => '',
        'sm' => 'py-8 px-5 md:py-10 md:px-10',
        'md' => 'py-10 px-5 md:py-16 md:px-10',
        'lg' => 'py-16 px-5 md:py-24 md:px-10',
        'xl' => 'py-20 px-5 md:py-32 md:px-10',
    ];

    /**
     * Create a new component instance.
     *
     * @param  string  $bg  Background color (named, Tailwind class, hex, or rgba)
     * @param  string  $padding  Padding preset (none, sm, md, lg, xl)
     * @param  string  $maxWidth  Max width (default: 1880px)
     * @param  bool  $container  Whether to add container-main class
     */
    public function __construct(
        string $bg = 'transparent',
        string $padding = 'md',
        string $maxWidth = '1880px',
        bool $container = false,
    ) {
        // Check additional colors first
        if (isset($this->additionalBgColors[$bg])) {
            $this->bgClass = $this->additionalBgColors[$bg];
            $this->bgStyle = '';
        } else {
            $resolved = $this->resolveBgColor($bg, 'transparent');
            $this->bgClass = $resolved['class'];
            $this->bgStyle = $resolved['style'];
        }
        $this->padding = $this->paddings[$padding] ?? $padding;
        $this->maxWidth = $maxWidth;
        $this->container = $container;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.section');
    }
}
