<?php

namespace App\View\Components\Sections;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Newsletter extends Component
{
    public string $title;

    public string $placeholder;

    public string $buttonText;

    public string $bgColor;

    /**
     * Create a new component instance.
     *
     * @param  string  $title  Section title/heading
     * @param  string  $placeholder  Input placeholder text
     * @param  string  $buttonText  Subscribe button text
     * @param  string  $bgColor  Background color (hex or Tailwind class)
     */
    public function __construct(
        string $title = 'COME HUNGRY, LEAVE INSPIRED. SIGN UP FOR TASTY UPDATES.',
        string $placeholder = 'Enter your Email',
        string $buttonText = 'SUBSCRIBE',
        string $bgColor = '#F3F4F6',
    ) {
        $this->title = $title;
        $this->placeholder = $placeholder;
        $this->buttonText = $buttonText;
        $this->bgColor = $bgColor;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.sections.newsletter');
    }
}
